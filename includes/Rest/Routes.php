<?php
/**
 * REST API: namespace wbdres/v1.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Rest;

use Wbdres\RealEstate\Frontend\Query;
use Wbdres\RealEstate\Leads\LeadRepository;
use Wbdres\RealEstate\Leads\LeadService;
use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;
use Wbdres\RealEstate\Support\Property;

defined( 'ABSPATH' ) || exit;

/**
 * Routes.
 */
final class Routes {

	const NS = 'wbdres/v1';

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Register routes.
	 */
	public function routes() {
		register_rest_route(
			self::NS,
			'/inquiries',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_inquiry' ),
				// Public form: protected by validation, honeypot, time trap and per-IP rate limiting.
				'permission_callback' => '__return_true',
				'args'                => array(
					'name'        => array(
						'type'      => 'string',
						'maxLength' => 100,
					),
					'email'       => array(
						'type'      => 'string',
						'maxLength' => 191,
					),
					'phone'       => array(
						'type'      => 'string',
						'maxLength' => 30,
					),
					'message'     => array(
						'type'      => 'string',
						'maxLength' => 3000,
					),
					'type'        => array(
						'type' => 'string',
						'enum' => array( 'inquiry', 'viewing', 'contact' ),
					),
					'property_id' => array( 'type' => 'integer' ),
					'agent_id'    => array( 'type' => 'integer' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/properties',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_properties' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'include'  => array(
						'type'        => 'string',
						'description' => 'Comma-separated property IDs.',
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 12,
						'minimum' => 1,
						'maximum' => 50,
					),
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/properties/(?P<id>\d+)/view',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'track_view' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/leads',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_leads' ),
				'permission_callback' => array( LeadService::class, 'can_access' ),
				'args'                => array(
					'status'   => array(
						'type' => 'string',
						'enum' => array_merge( array( '' ), array_keys( LeadRepository::statuses() ) ),
					),
					'search'   => array( 'type' => 'string' ),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 20,
						'minimum' => 1,
						'maximum' => 100,
					),
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/leads/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_lead' ),
					'permission_callback' => array( $this, 'can_view_lead' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_lead' ),
					'permission_callback' => array( $this, 'can_view_lead' ),
					'args'                => array(
						'status'   => array(
							'type' => 'string',
							'enum' => array_keys( LeadRepository::statuses() ),
						),
						'agent_id' => array( 'type' => 'integer' ),
						'note'     => array(
							'type'      => 'string',
							'maxLength' => 5000,
						),
					),
				),
			)
		);
	}

	/**
	 * POST /inquiries.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_inquiry( \WP_REST_Request $request ) {
		$raw = $request->get_params();
		$id  = LeadService::submit( is_array( $raw ) ? $raw : array() );
		if ( is_wp_error( $id ) ) {
			$messages = $id->get_error_messages();
			return new \WP_Error( $id->get_error_code(), implode( ' ', $messages ), $id->get_error_data() );
		}
		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Thank you! Your message has been sent. We will get back to you shortly.', 'wbd-real-estate-suite' ),
			),
			201
		);
	}

	/**
	 * GET /properties.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function list_properties( \WP_REST_Request $request ) {
		$args    = array(
			'post_type'      => Registrar::PROPERTY,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $request['per_page'],
			'paged'          => (int) $request['page'],
		);
		$include = array_slice( array_filter( array_map( 'absint', explode( ',', (string) $request['include'] ) ) ), 0, 50 );
		if ( $include ) {
			$args['post__in']       = $include;
			$args['orderby']        = 'post__in';
			$args['posts_per_page'] = count( $include );
		} else {
			$args = array_merge( $args, Query::to_query_args( Query::filters_from( $request->get_params() ) ) );
		}
		$q     = new \WP_Query( $args );
		$items = array();
		foreach ( $q->posts as $post ) {
			$p = Property::get( $post );
			if ( $p ) {
				$items[] = $p->to_array();
			}
		}
		$response = new \WP_REST_Response( $items );
		$response->header( 'X-WP-Total', (string) (int) $q->found_posts );
		$response->header( 'X-WP-TotalPages', (string) (int) $q->max_num_pages );
		return $response;
	}

	/**
	 * POST /properties/{id}/view: de-duplicated view counter.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function track_view( \WP_REST_Request $request ) {
		$id = (int) $request['id'];
		if ( ! Settings::get( 'enable_views', 1 ) || Registrar::PROPERTY !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
			return new \WP_REST_Response( array( 'counted' => false ), 200 );
		}
		$agent = $request->get_header( 'user_agent' );
		if ( $agent && preg_match( '/bot|crawl|spider|slurp|preview|lighthouse/i', (string) $agent ) ) {
			return new \WP_REST_Response( array( 'counted' => false ), 200 );
		}
		$key = 'wbdres_v_' . substr( hash( 'sha256', LeadService::ip_hash() . '|' . $id ), 0, 32 );
		if ( get_transient( $key ) ) {
			return new \WP_REST_Response( array( 'counted' => false ), 200 );
		}
		set_transient( $key, 1, 6 * HOUR_IN_SECONDS );
		$views = (int) get_post_meta( $id, '_wbdres_views', true );
		update_post_meta( $id, '_wbdres_views', $views + 1 );
		return new \WP_REST_Response( array( 'counted' => true ), 200 );
	}

	/**
	 * Permission for single lead routes.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	public function can_view_lead( \WP_REST_Request $request ) {
		return LeadService::can_access() && LeadService::can_view( LeadRepository::find( (int) $request['id'] ) );
	}

	/**
	 * Shape a lead for REST.
	 *
	 * @param object $lead Lead.
	 * @return array
	 */
	private function prepare_lead( $lead ) {
		return array(
			'id'             => (int) $lead->id,
			'type'           => $lead->type,
			'status'         => $lead->status,
			'name'           => $lead->name,
			'email'          => $lead->email,
			'phone'          => $lead->phone,
			'message'        => $lead->message,
			'property_id'    => (int) $lead->property_id,
			'property_title' => $lead->property_id ? get_the_title( (int) $lead->property_id ) : '',
			'agent_id'       => (int) $lead->agent_id,
			'preferred_date' => $lead->preferred_date,
			'preferred_time' => $lead->preferred_time,
			'created_at'     => mysql_to_rfc3339( $lead->created_at ),
		);
	}

	/**
	 * GET /leads.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function list_leads( \WP_REST_Request $request ) {
		$result   = LeadRepository::query(
			array(
				'status'    => (string) $request['status'],
				'search'    => sanitize_text_field( (string) $request['search'] ),
				'per_page'  => (int) $request['per_page'],
				'page'      => (int) $request['page'],
				'agent_ids' => LeadService::scope(),
			)
		);
		$response = new \WP_REST_Response( array_map( array( $this, 'prepare_lead' ), $result['items'] ) );
		$response->header( 'X-WP-Total', (string) $result['total'] );
		return $response;
	}

	/**
	 * GET /leads/{id}.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_lead( \WP_REST_Request $request ) {
		return new \WP_REST_Response( $this->prepare_lead( LeadRepository::find( (int) $request['id'] ) ) );
	}

	/**
	 * PATCH /leads/{id}.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update_lead( \WP_REST_Request $request ) {
		$lead = LeadRepository::find( (int) $request['id'] );
		if ( $request->has_param( 'status' ) ) {
			LeadService::change_status( $lead, (string) $request['status'] );
		}
		if ( $request->has_param( 'agent_id' ) && current_user_can( 'wbdres_manage_leads' ) ) {
			LeadService::assign( $lead, (int) $request['agent_id'] );
		}
		if ( $request->has_param( 'note' ) && '' !== trim( (string) $request['note'] ) ) {
			LeadRepository::add_note( $lead->id, sanitize_textarea_field( (string) $request['note'] ) );
		}
		return new \WP_REST_Response( $this->prepare_lead( LeadRepository::find( $lead->id ) ) );
	}
}
