<?php
/**
 * Lead business logic: validation, spam protection, rate limiting, access control.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Leads;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * LeadService.
 */
final class LeadService {

	/**
	 * Validate and store a public form submission.
	 *
	 * @param array $raw Unslashed request data.
	 * @return int|\WP_Error Lead ID.
	 */
	public static function submit( array $raw ) {
		// Honeypot.
		if ( ! empty( $raw['website'] ) ) {
			return new \WP_Error( 'wbdres_spam', __( 'Your message could not be sent.', 'wbd-real-estate-suite' ), array( 'status' => 400 ) );
		}
		// Time trap: humans need more than 2 seconds; stale forms (>1 day) are fine too.
		$ts = isset( $raw['_ts'] ) ? absint( $raw['_ts'] ) : 0;
		if ( $ts && ( time() - $ts ) < 2 ) {
			return new \WP_Error( 'wbdres_spam', __( 'Please take a moment before sending the form.', 'wbd-real-estate-suite' ), array( 'status' => 400 ) );
		}

		$ip_hash = self::ip_hash();
		$limit   = (int) Settings::get( 'rate_limit', 5 );
		$key     = 'wbdres_rl_' . substr( $ip_hash, 0, 32 );
		$hits    = (int) get_transient( $key );
		if ( $hits >= $limit ) {
			return new \WP_Error( 'wbdres_rate_limited', __( 'Too many messages were sent from your connection. Please try again later.', 'wbd-real-estate-suite' ), array( 'status' => 429 ) );
		}

		$type = isset( $raw['type'] ) ? sanitize_key( $raw['type'] ) : 'inquiry';
		$type = in_array( $type, array( 'inquiry', 'viewing', 'contact' ), true ) ? $type : 'inquiry';

		$data = array(
			'type'           => $type,
			'name'           => mb_substr( sanitize_text_field( (string) ( $raw['name'] ?? '' ) ), 0, 100 ),
			'email'          => sanitize_email( (string) ( $raw['email'] ?? '' ) ),
			'phone'          => mb_substr( preg_replace( '/[^0-9+()\-\s]/', '', (string) ( $raw['phone'] ?? '' ) ), 0, 30 ),
			'message'        => mb_substr( sanitize_textarea_field( (string) ( $raw['message'] ?? '' ) ), 0, 3000 ),
			'preferred_date' => '',
			'preferred_time' => '',
			'property_id'    => absint( $raw['property_id'] ?? 0 ),
			'agent_id'       => absint( $raw['agent_id'] ?? 0 ),
			'user_id'        => get_current_user_id(),
			'consent'        => ! empty( $raw['consent'] ),
			'ip_hash'        => $ip_hash,
		);

		$errors = new \WP_Error();
		if ( mb_strlen( $data['name'] ) < 2 ) {
			$errors->add( 'name', __( 'Please enter your name.', 'wbd-real-estate-suite' ) );
		}
		if ( ! is_email( $data['email'] ) ) {
			$errors->add( 'email', __( 'Please enter a valid email address.', 'wbd-real-estate-suite' ) );
		}
		if ( 'viewing' === $type && strlen( preg_replace( '/\D/', '', $data['phone'] ) ) < 6 ) {
			$errors->add( 'phone', __( 'Please enter a phone number so the agent can confirm your viewing.', 'wbd-real-estate-suite' ) );
		}
		if ( 'contact' === $type && mb_strlen( trim( $data['message'] ) ) < 5 ) {
			$errors->add( 'message', __( 'Please write a short message.', 'wbd-real-estate-suite' ) );
		}
		if ( 'viewing' === $type ) {
			$date = isset( $raw['preferred_date'] ) ? sanitize_text_field( (string) $raw['preferred_date'] ) : '';
			$dt   = \DateTime::createFromFormat( 'Y-m-d', $date );
			if ( ! $dt || $dt->format( 'Y-m-d' ) !== $date || $date < current_time( 'Y-m-d' ) ) {
				$errors->add( 'preferred_date', __( 'Please choose a valid future date.', 'wbd-real-estate-suite' ) );
			} else {
				$data['preferred_date'] = $date;
			}
			$time                   = isset( $raw['preferred_time'] ) ? sanitize_key( $raw['preferred_time'] ) : '';
			$data['preferred_time'] = in_array( $time, array( 'morning', 'afternoon', 'evening' ), true ) ? $time : '';
		}
		if ( Settings::get( 'consent_required', 1 ) && ! $data['consent'] ) {
			$errors->add( 'consent', __( 'Please accept the privacy consent to continue.', 'wbd-real-estate-suite' ) );
		}
		// Link checks: block messages stuffed with links (common spam pattern).
		if ( preg_match_all( '#https?://#i', $data['message'] ) > 3 ) {
			$errors->add( 'message', __( 'Please remove links from your message.', 'wbd-real-estate-suite' ) );
		}

		// Property & agent must be real and public.
		if ( $data['property_id'] ) {
			$property = get_post( $data['property_id'] );
			if ( ! $property || Registrar::PROPERTY !== $property->post_type || 'publish' !== $property->post_status ) {
				$data['property_id'] = 0;
			} elseif ( ! $data['agent_id'] ) {
				$data['agent_id'] = absint( get_post_meta( $property->ID, '_wbdres_agent_id', true ) );
			}
		}
		if ( $data['agent_id'] ) {
			$agent = get_post( $data['agent_id'] );
			if ( ! $agent || Registrar::AGENT !== $agent->post_type || 'publish' !== $agent->post_status ) {
				$data['agent_id'] = 0;
			}
		}

		if ( $errors->has_errors() ) {
			$errors->add_data( array( 'status' => 422 ) );
			return $errors;
		}

		$referer            = wp_get_referer();
		$data['source_url'] = $referer && wp_validate_redirect( $referer, false ) ? mb_substr( esc_url_raw( $referer ), 0, 500 ) : '';

		/**
		 * Filter lead data before it is saved. Return WP_Error to reject (e.g. external spam check).
		 *
		 * @param array|\WP_Error $data Lead data.
		 * @param array           $raw  Raw request.
		 */
		$data = apply_filters( 'wbdres_pre_insert_lead', $data, $raw );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$id = LeadRepository::insert( $data );
		if ( ! $id ) {
			return new \WP_Error( 'wbdres_db', __( 'Your message could not be saved. Please try again.', 'wbd-real-estate-suite' ), array( 'status' => 500 ) );
		}
		set_transient( $key, $hits + 1, HOUR_IN_SECONDS );
		LeadRepository::add_note( $id, __( 'Lead created from website form.', 'wbd-real-estate-suite' ), 'system' );

		/**
		 * Fires after a lead is created from a public form.
		 *
		 * @param int   $id   Lead ID.
		 * @param array $data Lead data.
		 */
		do_action( 'wbdres_lead_created', $id, $data );
		return $id;
	}

	/**
	 * Salted hash of the visitor IP (raw IPs are never stored).
	 *
	 * @return string
	 */
	public static function ip_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) );
	}

	/**
	 * Agent post IDs linked to the current user.
	 *
	 * @param int $user_id User.
	 * @return int[]
	 */
	public static function agent_ids_for_user( $user_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}
		$ids = get_posts(
			array(
				'post_type'      => Registrar::AGENT,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 20,
				'meta_key'       => '_wbdres_agent_user_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $user_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return array_map( 'intval', $ids );
	}

	/**
	 * Can the current user see leads at all?
	 *
	 * @return bool
	 */
	public static function can_access() {
		return current_user_can( 'wbdres_manage_leads' ) || current_user_can( 'wbdres_view_own_leads' );
	}

	/**
	 * Agent scope for the current user: null = all leads, array = restricted to these agent IDs.
	 *
	 * @return int[]|null
	 */
	public static function scope() {
		if ( current_user_can( 'wbdres_manage_leads' ) ) {
			return null;
		}
		return self::agent_ids_for_user();
	}

	/**
	 * Can the current user access a specific lead?
	 *
	 * @param object|null $lead Lead row.
	 * @return bool
	 */
	public static function can_view( $lead ) {
		if ( ! $lead ) {
			return false;
		}
		$scope = self::scope();
		return null === $scope || in_array( (int) $lead->agent_id, $scope, true );
	}

	/**
	 * Change status with activity log + action.
	 *
	 * @param object $lead   Lead.
	 * @param string $status New status.
	 * @return bool
	 */
	public static function change_status( $lead, $status ) {
		$statuses = LeadRepository::statuses();
		if ( ! isset( $statuses[ $status ] ) || $lead->status === $status ) {
			return false;
		}
		LeadRepository::update( $lead->id, array( 'status' => $status ) );
		/* translators: 1: old status, 2: new status. */
		LeadRepository::add_note( $lead->id, sprintf( __( 'Status changed from %1$s to %2$s.', 'wbd-real-estate-suite' ), $statuses[ $lead->status ] ?? $lead->status, $statuses[ $status ] ), 'status' );
		do_action( 'wbdres_lead_status_changed', (int) $lead->id, $status, $lead->status );
		return true;
	}

	/**
	 * Assign to agent with activity log + action.
	 *
	 * @param object $lead     Lead.
	 * @param int    $agent_id Agent post ID (0 = unassigned).
	 * @return bool
	 */
	public static function assign( $lead, $agent_id ) {
		$agent_id = absint( $agent_id );
		if ( (int) $lead->agent_id === $agent_id ) {
			return false;
		}
		if ( $agent_id && Registrar::AGENT !== get_post_type( $agent_id ) ) {
			return false;
		}
		LeadRepository::update( $lead->id, array( 'agent_id' => $agent_id ) );
		$name = $agent_id ? get_the_title( $agent_id ) : __( 'nobody', 'wbd-real-estate-suite' );
		/* translators: %s: agent name. */
		LeadRepository::add_note( $lead->id, sprintf( __( 'Assigned to %s.', 'wbd-real-estate-suite' ), $name ), 'assign' );
		if ( $agent_id ) {
			do_action( 'wbdres_lead_assigned', (int) $lead->id, $agent_id );
		}
		return true;
	}
}
