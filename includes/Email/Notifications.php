<?php
/**
 * Event → email wiring with delivery logging to the lead activity timeline.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Email;

use Wbdres\RealEstate\Leads\LeadRepository;
use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Notifications.
 */
final class Notifications {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'wbdres_lead_created', array( $this, 'lead_created' ), 10, 1 );
		add_action( 'wbdres_lead_assigned', array( $this, 'lead_assigned' ), 10, 2 );
		add_action( 'wbdres_property_submitted', array( $this, 'property_submitted' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'maybe_approved' ), 10, 3 );
		add_action( 'wbdres_property_rejected', array( $this, 'property_rejected' ), 10, 2 );
	}

	/**
	 * Admin recipients.
	 *
	 * @return string[]
	 */
	public static function admin_recipients() {
		$list = array_filter( array_map( 'trim', explode( ',', (string) Settings::get( 'notify_emails', '' ) ) ), 'is_email' );
		if ( ! $list ) {
			$list = array( get_option( 'admin_email' ) );
		}
		return array_values( array_unique( $list ) );
	}

	/**
	 * Variables for a lead.
	 *
	 * @param object $lead Lead row.
	 * @return array
	 */
	public static function lead_vars( $lead ) {
		$types = LeadRepository::types();
		$times = array(
			'morning'   => __( 'Morning', 'wbd-real-estate-suite' ),
			'afternoon' => __( 'Afternoon', 'wbd-real-estate-suite' ),
			'evening'   => __( 'Evening', 'wbd-real-estate-suite' ),
		);
		$pid   = (int) $lead->property_id;
		$aid   = (int) $lead->agent_id;
		return array(
			'lead_name'      => $lead->name,
			'lead_email'     => $lead->email,
			'lead_phone'     => $lead->phone ? $lead->phone : '—',
			'lead_message'   => $lead->message ? $lead->message : '—',
			'lead_type'      => \Wbdres\RealEstate\Support\Format::lower( $types[ $lead->type ] ?? $lead->type ),
			'lead_status'    => LeadRepository::statuses()[ $lead->status ] ?? $lead->status,
			'preferred_date' => $lead->preferred_date ? date_i18n( get_option( 'date_format' ), strtotime( $lead->preferred_date ) ) : '—',
			'preferred_time' => $times[ $lead->preferred_time ] ?? '',
			'lead_admin_url' => admin_url( 'admin.php?page=wbdres-leads&action=view&lead=' . (int) $lead->id ),
			'property_title' => $pid ? wp_specialchars_decode( get_the_title( $pid ), ENT_QUOTES ) : __( 'General inquiry', 'wbd-real-estate-suite' ),
			'property_url'   => $pid ? get_permalink( $pid ) : '',
			'agent_name'     => $aid ? wp_specialchars_decode( get_the_title( $aid ), ENT_QUOTES ) : '',
		);
	}

	/**
	 * Agent email address.
	 *
	 * @param int $agent_id Agent post.
	 * @return string
	 */
	public static function agent_email( $agent_id ) {
		if ( ! $agent_id ) {
			return '';
		}
		$email = (string) get_post_meta( $agent_id, '_wbdres_agent_email', true );
		if ( ! is_email( $email ) ) {
			$user_id = (int) get_post_meta( $agent_id, '_wbdres_agent_user_id', true );
			$user    = $user_id ? get_userdata( $user_id ) : null;
			$email   = $user ? $user->user_email : '';
		}
		return is_email( $email ) ? $email : '';
	}

	/**
	 * New lead.
	 *
	 * @param int $lead_id Lead.
	 */
	public function lead_created( $lead_id ) {
		$lead = LeadRepository::find( $lead_id );
		if ( ! $lead ) {
			return;
		}
		$vars = self::lead_vars( $lead );
		$log  = array();

		$ok    = Mailer::send( 'lead_admin', self::admin_recipients(), $vars, $lead->email );
		$log[] = sprintf( '%s: %s', __( 'Admin notification', 'wbd-real-estate-suite' ), $ok ? __( 'sent', 'wbd-real-estate-suite' ) : __( 'not sent', 'wbd-real-estate-suite' ) );

		if ( Settings::get( 'notify_agent', 1 ) ) {
			$agent_email = self::agent_email( (int) $lead->agent_id );
			if ( $agent_email && ! in_array( $agent_email, self::admin_recipients(), true ) ) {
				$ok    = Mailer::send( 'lead_agent', $agent_email, $vars, $lead->email );
				$log[] = sprintf( '%s: %s', __( 'Agent notification', 'wbd-real-estate-suite' ), $ok ? __( 'sent', 'wbd-real-estate-suite' ) : __( 'not sent', 'wbd-real-estate-suite' ) );
			}
		}
		if ( Settings::get( 'autoreply', 1 ) ) {
			$ok    = Mailer::send( 'lead_autoreply', $lead->email, $vars );
			$log[] = sprintf( '%s: %s', __( 'Auto-reply', 'wbd-real-estate-suite' ), $ok ? __( 'sent', 'wbd-real-estate-suite' ) : __( 'not sent', 'wbd-real-estate-suite' ) );
		}
		LeadRepository::add_note( $lead_id, implode( ' · ', $log ), 'email' );
	}

	/**
	 * Lead assigned.
	 *
	 * @param int $lead_id  Lead.
	 * @param int $agent_id Agent.
	 */
	public function lead_assigned( $lead_id, $agent_id ) {
		$lead  = LeadRepository::find( $lead_id );
		$email = self::agent_email( $agent_id );
		if ( ! $lead || ! $email ) {
			return;
		}
		$ok = Mailer::send( 'lead_assigned', $email, self::lead_vars( $lead ) );
		LeadRepository::add_note( $lead_id, __( 'Assignment email', 'wbd-real-estate-suite' ) . ': ' . ( $ok ? __( 'sent', 'wbd-real-estate-suite' ) : __( 'not sent', 'wbd-real-estate-suite' ) ), 'email' );
	}

	/**
	 * Submission received.
	 *
	 * @param int $post_id Property.
	 * @param int $user_id User.
	 */
	public function property_submitted( $post_id, $user_id ) {
		if ( 'pending' !== get_post_status( $post_id ) ) {
			return;
		}
		$user = get_userdata( $user_id );
		Mailer::send(
			'property_submitted',
			self::admin_recipients(),
			array(
				'property_title'    => wp_specialchars_decode( get_the_title( $post_id ), ENT_QUOTES ),
				'property_edit_url' => admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' ),
				'submitter_name'    => $user ? $user->display_name : '',
			)
		);
	}

	/**
	 * Pending → publish for frontend submissions.
	 *
	 * @param string   $new_status New.
	 * @param string   $old_status Old.
	 * @param \WP_Post $post       Post.
	 */
	public function maybe_approved( $new_status, $old_status, $post ) {
		if ( Registrar::PROPERTY !== $post->post_type || 'publish' !== $new_status || 'pending' !== $old_status || ! get_post_meta( $post->ID, '_wbdres_submitted', true ) ) {
			return;
		}
		$user = get_userdata( (int) $post->post_author );
		if ( ! $user ) {
			return;
		}
		Mailer::send(
			'property_approved',
			$user->user_email,
			array(
				'property_title' => wp_specialchars_decode( get_the_title( $post ), ENT_QUOTES ),
				'property_url'   => get_permalink( $post ),
				'submitter_name' => $user->display_name,
			)
		);
	}

	/**
	 * Rejected submission.
	 *
	 * @param int    $post_id Property.
	 * @param string $reason  Reason.
	 */
	public function property_rejected( $post_id, $reason ) {
		$post = get_post( $post_id );
		$user = $post ? get_userdata( (int) $post->post_author ) : null;
		if ( ! $user ) {
			return;
		}
		Mailer::send(
			'property_rejected',
			$user->user_email,
			array(
				'property_title' => wp_specialchars_decode( get_the_title( $post ), ENT_QUOTES ),
				'submitter_name' => $user->display_name,
				'reason'         => $reason ? $reason : __( 'Please review the listing details and photos.', 'wbd-real-estate-suite' ),
			)
		);
	}
}
