<?php
/**
 * Property meta registration (REST-enabled, sanitized, permission-checked).
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Meta;

use Wbdres\RealEstate\PostTypes\Registrar;

defined( 'ABSPATH' ) || exit;

/**
 * Registers all `_wbdres_*` property meta keys.
 */
final class PropertyMeta {

	/**
	 * Meta schema: key => [type, default, sanitizer].
	 *
	 * @return array
	 */
	public static function schema() {
		return array(
			'_wbdres_price'            => array( 'number', 0, array( __CLASS__, 'sanitize_money' ) ),
			'_wbdres_price_suffix'     => array( 'string', '', array( __CLASS__, 'sanitize_short' ) ),
			'_wbdres_price_on_request' => array( 'boolean', false, 'rest_sanitize_boolean' ),
			'_wbdres_bedrooms'         => array( 'integer', 0, 'absint' ),
			'_wbdres_bathrooms'        => array( 'number', 0, array( __CLASS__, 'sanitize_half' ) ),
			'_wbdres_area'             => array( 'number', 0, array( __CLASS__, 'sanitize_money' ) ),
			'_wbdres_land_area'        => array( 'number', 0, array( __CLASS__, 'sanitize_money' ) ),
			'_wbdres_garages'          => array( 'integer', 0, 'absint' ),
			'_wbdres_year_built'       => array( 'integer', 0, array( __CLASS__, 'sanitize_year' ) ),
			'_wbdres_reference'        => array( 'string', '', array( __CLASS__, 'sanitize_short' ) ),
			'_wbdres_address'          => array( 'string', '', 'sanitize_text_field' ),
			'_wbdres_postal_code'      => array( 'string', '', array( __CLASS__, 'sanitize_short' ) ),
			'_wbdres_country'          => array( 'string', '', array( __CLASS__, 'sanitize_short' ) ),
			'_wbdres_lat'              => array( 'number', 0, array( __CLASS__, 'sanitize_lat' ) ),
			'_wbdres_lng'              => array( 'number', 0, array( __CLASS__, 'sanitize_lng' ) ),
			'_wbdres_hide_map'         => array( 'boolean', false, 'rest_sanitize_boolean' ),
			'_wbdres_video_url'        => array( 'string', '', 'esc_url_raw' ),
			'_wbdres_tour_url'         => array( 'string', '', 'esc_url_raw' ),
			'_wbdres_featured'         => array( 'boolean', false, 'rest_sanitize_boolean' ),
			'_wbdres_agent_id'         => array( 'integer', 0, 'absint' ),
		);
	}

	/**
	 * Hook registration.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_meta' ), 6 );
	}

	/**
	 * Register meta keys.
	 */
	public function register_meta() {
		foreach ( self::schema() as $key => $def ) {
			register_post_meta(
				Registrar::PROPERTY,
				$key,
				array(
					'type'              => $def[0],
					'single'            => true,
					'default'           => $def[1],
					'show_in_rest'      => true,
					'sanitize_callback' => $def[2],
					'auth_callback'     => array( __CLASS__, 'can_edit' ),
				)
			);
		}

		register_post_meta(
			Registrar::PROPERTY,
			'_wbdres_gallery',
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => array( __CLASS__, 'sanitize_ids' ),
				'auth_callback'     => array( __CLASS__, 'can_edit' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
			)
		);

		// Read-only counters: not exposed for editing.
		register_post_meta(
			Registrar::PROPERTY,
			'_wbdres_views',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => false,
				'sanitize_callback' => 'absint',
				'auth_callback'     => '__return_false',
			)
		);
	}

	/**
	 * Meta auth.
	 *
	 * @param bool   $allowed  Allowed.
	 * @param string $meta_key Key.
	 * @param int    $post_id  Post.
	 * @return bool
	 */
	public static function can_edit( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Non-negative number with 2 decimals.
	 *
	 * @param mixed $value Value.
	 * @return float
	 */
	public static function sanitize_money( $value ) {
		$value = is_string( $value ) ? preg_replace( '/[^0-9.\-]/', '', $value ) : $value;
		return round( max( 0, (float) $value ), 2 );
	}

	/**
	 * Round to nearest .5.
	 *
	 * @param mixed $value Value.
	 * @return float
	 */
	public static function sanitize_half( $value ) {
		return round( max( 0, (float) $value ) * 2 ) / 2;
	}

	/**
	 * Plausible year or 0.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	public static function sanitize_year( $value ) {
		$year = absint( $value );
		return ( $year >= 1000 && $year <= 2200 ) ? $year : 0;
	}

	/**
	 * Short text (max 100 chars).
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function sanitize_short( $value ) {
		return mb_substr( sanitize_text_field( (string) $value ), 0, 100 );
	}

	/**
	 * Latitude.
	 *
	 * @param mixed $value Value.
	 * @return float
	 */
	public static function sanitize_lat( $value ) {
		return round( min( 90, max( -90, (float) $value ) ), 7 );
	}

	/**
	 * Longitude.
	 *
	 * @param mixed $value Value.
	 * @return float
	 */
	public static function sanitize_lng( $value ) {
		return round( min( 180, max( -180, (float) $value ) ), 7 );
	}

	/**
	 * Array of attachment IDs (max 60).
	 *
	 * @param mixed $value Value.
	 * @return int[]
	 */
	public static function sanitize_ids( $value ) {
		if ( ! is_array( $value ) ) {
			$value = array_filter( explode( ',', (string) $value ) );
		}
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
		return array_slice( $ids, 0, 60 );
	}
}
