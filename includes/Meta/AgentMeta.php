<?php
/**
 * Agent meta registration.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Meta;

use Wbdres\RealEstate\PostTypes\Registrar;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `_wbdres_agent_*` meta and hides private contact fields from public REST output.
 */
final class AgentMeta {

	/**
	 * Schema.
	 *
	 * @return array
	 */
	public static function schema() {
		return array(
			'_wbdres_agent_title'     => array( 'string', 'sanitize_text_field' ),
			'_wbdres_agent_email'     => array( 'string', 'sanitize_email' ),
			'_wbdres_agent_phone'     => array( 'string', array( __CLASS__, 'sanitize_phone' ) ),
			'_wbdres_agent_whatsapp'  => array( 'string', array( __CLASS__, 'sanitize_phone' ) ),
			'_wbdres_agent_license'   => array( 'string', 'sanitize_text_field' ),
			'_wbdres_agent_agency'    => array( 'string', 'sanitize_text_field' ),
			'_wbdres_agent_languages' => array( 'string', 'sanitize_text_field' ),
			'_wbdres_agent_website'   => array( 'string', 'esc_url_raw' ),
			'_wbdres_agent_facebook'  => array( 'string', 'esc_url_raw' ),
			'_wbdres_agent_linkedin'  => array( 'string', 'esc_url_raw' ),
			'_wbdres_agent_instagram' => array( 'string', 'esc_url_raw' ),
			'_wbdres_agent_x'         => array( 'string', 'esc_url_raw' ),
			'_wbdres_agent_user_id'   => array( 'integer', 'absint' ),
		);
	}

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_meta' ), 6 );
		add_filter( 'rest_prepare_' . Registrar::AGENT, array( $this, 'protect_private_fields' ), 10, 2 );
	}

	/**
	 * Register meta.
	 */
	public function register_meta() {
		foreach ( self::schema() as $key => $def ) {
			register_post_meta(
				Registrar::AGENT,
				$key,
				array(
					'type'              => $def[0],
					'single'            => true,
					'default'           => 'integer' === $def[0] ? 0 : '',
					'show_in_rest'      => true,
					'sanitize_callback' => $def[1],
					'auth_callback'     => array( PropertyMeta::class, 'can_edit' ),
				)
			);
		}
	}

	/**
	 * Remove the agent's email and linked user ID from REST responses for visitors.
	 * Visitors contact agents through the inquiry form instead (anti-harvesting).
	 *
	 * @param \WP_REST_Response $response Response.
	 * @param \WP_Post          $post     Post.
	 * @return \WP_REST_Response
	 */
	public function protect_private_fields( $response, $post ) {
		if ( current_user_can( 'edit_post', $post->ID ) ) {
			return $response;
		}
		$data = $response->get_data();
		if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
			unset( $data['meta']['_wbdres_agent_email'], $data['meta']['_wbdres_agent_user_id'] );
			$response->set_data( $data );
		}
		return $response;
	}

	/**
	 * Phone: digits, spaces, + ( ) - only.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function sanitize_phone( $value ) {
		return mb_substr( preg_replace( '/[^0-9+()\-\s]/', '', (string) $value ), 0, 30 );
	}
}
