<?php
/**
 * Settings access and sanitization (single option row).
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate;

defined( 'ABSPATH' ) || exit;

/**
 * Typed access to the `wbdres_settings` option.
 */
final class Settings {

	const OPTION = 'wbdres_settings';

	/**
	 * Request cache.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Default values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'currency_code'       => 'USD',
			'currency_symbol'     => '$',
			'currency_position'   => 'before',
			'thousand_sep'        => ',',
			'decimal_sep'         => '.',
			'decimals'            => 0,
			'area_unit'           => 'sqft',
			'property_slug'       => 'property',
			'archive_slug'        => 'properties',
			'agent_slug'          => 'agent',
			'per_page'            => 12,
			'accent_color'        => '#0f766e',
			'auto_single_layout'  => 1,
			'enable_map'          => 1,
			'map_consent'         => 1,
			'enable_favorites'    => 1,
			'enable_compare'      => 1,
			'enable_views'        => 1,
			'enable_schema'       => 1,
			'enable_submission'   => 1,
			'submission_role'     => 'subscriber',
			'submission_max_imgs' => 8,
			'notify_emails'       => '',
			'notify_agent'        => 1,
			'autoreply'           => 1,
			'consent_required'    => 1,
			'consent_text'        => __( 'I agree that my submitted data is stored and used to respond to my inquiry.', 'wbd-real-estate-suite' ),
			'privacy_page_id'     => 0,
			'lead_retention_days' => 0,
			'rate_limit'          => 5,
			'mortgage_rate'       => 6.5,
			'mortgage_years'      => 30,
			'mortgage_down'       => 20,
			'delete_on_uninstall' => 0,
			'whatsapp_number'     => '',
			'whatsapp_float'      => 'off',
			/* translators: {title} and {url} are placeholders, keep them as they are. */
			'whatsapp_message'    => __( 'Hello! I am interested in this property: {title} {url}', 'wbd-real-estate-suite' ),
		);
	}

	/**
	 * All settings merged with defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		}
		return self::$cache;
	}

	/**
	 * Get one setting.
	 *
	 * @param string $key      Key.
	 * @param mixed  $fallback Fallback.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Flush cache (after save).
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Sanitize the full settings array.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$current  = self::all();
		$out      = array();

		$checkboxes = array( 'auto_single_layout', 'enable_map', 'map_consent', 'enable_favorites', 'enable_compare', 'enable_views', 'enable_schema', 'enable_submission', 'notify_agent', 'autoreply', 'consent_required', 'delete_on_uninstall' );

		// Checkboxes rendered on the submitted tab are listed in `_present`; others keep their value.
		$present = isset( $input['_present'] ) && is_array( $input['_present'] ) ? array_map( 'sanitize_key', $input['_present'] ) : $checkboxes;

		foreach ( $defaults as $key => $default ) {
			if ( in_array( $key, $checkboxes, true ) ) {
				$out[ $key ] = in_array( $key, $present, true ) ? ( empty( $input[ $key ] ) ? 0 : 1 ) : (int) $current[ $key ];
				continue;
			}
			$value = array_key_exists( $key, $input ) ? $input[ $key ] : $current[ $key ];

			switch ( $key ) {
				case 'currency_code':
					$value = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $value ) );
					$value = 3 === strlen( $value ) ? $value : $default;
					break;
				case 'currency_symbol':
					$value = mb_substr( sanitize_text_field( (string) $value ), 0, 6 );
					break;
				case 'currency_position':
					$value = in_array( $value, array( 'before', 'after', 'before_space', 'after_space' ), true ) ? $value : $default;
					break;
				case 'thousand_sep':
				case 'decimal_sep':
					$value = mb_substr( sanitize_text_field( (string) $value ), 0, 2 );
					break;
				case 'decimals':
					$value = min( 4, max( 0, absint( $value ) ) );
					break;
				case 'area_unit':
					$value = in_array( $value, array( 'sqft', 'sqm', 'acre', 'hectare', 'katha', 'marla', 'tsubo', 'ping' ), true ) ? $value : $default;
					break;
				case 'property_slug':
				case 'archive_slug':
				case 'agent_slug':
					$value = sanitize_title( (string) $value );
					$value = '' === $value ? $default : $value;
					break;
				case 'accent_color':
					$value = sanitize_hex_color( (string) $value );
					$value = $value ? $value : $default;
					break;
				case 'per_page':
					$value = min( 100, max( 1, absint( $value ) ) );
					break;
				case 'submission_role':
					$value = in_array( $value, array( 'subscriber', 'wbdres_agent', 'author' ), true ) ? $value : $default;
					break;
				case 'submission_max_imgs':
					$value = min( 20, max( 0, absint( $value ) ) );
					break;
				case 'notify_emails':
					$emails = array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', (string) $value ) ) ) );
					$value  = implode( ', ', array_filter( $emails, 'is_email' ) );
					break;
				case 'consent_text':
					$value = wp_kses(
						(string) $value,
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					);
					break;
				case 'privacy_page_id':
					$value = absint( $value );
					break;
				case 'lead_retention_days':
					$value = min( 3650, absint( $value ) );
					break;
				case 'rate_limit':
					$value = min( 100, max( 1, absint( $value ) ) );
					break;
				case 'mortgage_rate':
				case 'mortgage_down':
					$value = min( 100, max( 0, (float) $value ) );
					break;
				case 'mortgage_years':
					$value = min( 50, max( 1, absint( $value ) ) );
					break;
				case 'whatsapp_number':
					$value = mb_substr( preg_replace( '/[^0-9+()\-\s]/', '', (string) $value ), 0, 30 );
					break;
				case 'whatsapp_float':
					$value = in_array( $value, array( 'off', 'plugin', 'all' ), true ) ? $value : $default;
					break;
				case 'whatsapp_message':
					$value = mb_substr( sanitize_textarea_field( (string) $value ), 0, 300 );
					$value = '' === trim( $value ) ? $default : $value;
					break;
				default:
					$value = sanitize_text_field( (string) $value );
			}
			$out[ $key ] = $value;
		}

		if ( $out['property_slug'] !== $current['property_slug'] || $out['archive_slug'] !== $current['archive_slug'] || $out['agent_slug'] !== $current['agent_slug'] ) {
			update_option( 'wbdres_flush_rewrite', 1, false );
		}

		self::flush();
		return $out;
	}

	/**
	 * Area unit labels.
	 *
	 * @return array
	 */
	public static function area_units() {
		return array(
			'sqft'    => __( 'sq ft', 'wbd-real-estate-suite' ),
			'sqm'     => __( 'm²', 'wbd-real-estate-suite' ),
			'acre'    => __( 'acres', 'wbd-real-estate-suite' ),
			'hectare' => __( 'ha', 'wbd-real-estate-suite' ),
			'katha'   => __( 'katha', 'wbd-real-estate-suite' ),
			'marla'   => __( 'marla', 'wbd-real-estate-suite' ),
			'tsubo'   => __( 'tsubo', 'wbd-real-estate-suite' ),
			'ping'    => __( 'ping', 'wbd-real-estate-suite' ),
		);
	}
}
