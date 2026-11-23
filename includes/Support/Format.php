<?php
/**
 * Locale-aware formatting helpers.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Support;

use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Price, area and number formatting.
 */
final class Format {

	/**
	 * Format a number with the configured separators.
	 *
	 * @param float    $number   Number.
	 * @param int|null $decimals Decimals.
	 * @return string
	 */
	public static function number( $number, $decimals = null ) {
		$decimals = null === $decimals ? (int) Settings::get( 'decimals', 0 ) : (int) $decimals;
		return number_format( (float) $number, $decimals, (string) Settings::get( 'decimal_sep', '.' ), (string) Settings::get( 'thousand_sep', ',' ) );
	}

	/**
	 * Format a price.
	 *
	 * @param float  $amount     Amount.
	 * @param string $suffix     Suffix such as "/month".
	 * @param bool   $on_request Price on request.
	 * @return string Plain text (escape on output).
	 */
	public static function price( $amount, $suffix = '', $on_request = false ) {
		if ( $on_request || (float) $amount <= 0 ) {
			return __( 'Price on request', 'wbd-real-estate-suite' );
		}
		$symbol = (string) Settings::get( 'currency_symbol', '$' );
		$num    = self::number( $amount );
		switch ( Settings::get( 'currency_position', 'before' ) ) {
			case 'after':
				$out = $num . $symbol;
				break;
			case 'before_space':
				$out = $symbol . ' ' . $num;
				break;
			case 'after_space':
				$out = $num . ' ' . $symbol;
				break;
			default:
				$out = $symbol . $num;
		}
		$out = $suffix ? $out . ' ' . $suffix : $out;

		/**
		 * Filter the formatted property price.
		 *
		 * @param string $out    Formatted price.
		 * @param float  $amount Raw amount.
		 * @param string $suffix Suffix.
		 */
		return apply_filters( 'wbdres_format_price', $out, $amount, $suffix );
	}

	/**
	 * Format an area with unit.
	 *
	 * @param float $area Area.
	 * @return string
	 */
	public static function area( $area ) {
		if ( (float) $area <= 0 ) {
			return '';
		}
		$units = Settings::area_units();
		$unit  = Settings::get( 'area_unit', 'sqft' );
		$dec   = floor( (float) $area ) === (float) $area ? 0 : 1;
		return self::number( $area, $dec ) . ' ' . ( isset( $units[ $unit ] ) ? $units[ $unit ] : $unit );
	}

	/**
	 * Bathrooms (may be .5).
	 *
	 * @param float $value Value.
	 * @return string
	 */
	public static function half( $value ) {
		$value = (float) $value;
		return floor( $value ) === $value ? (string) (int) $value : self::number( $value, 1 );
	}

	/**
	 * Multibyte-safe uppercase (falls back when mbstring is unavailable).
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function upper( $text ) {
		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $text ) : strtoupper( $text );
	}

	/**
	 * Multibyte-safe lowercase.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function lower( $text ) {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text ) : strtolower( $text );
	}
}
