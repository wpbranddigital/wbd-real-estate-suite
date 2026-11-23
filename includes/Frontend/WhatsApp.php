<?php
/**
 * WhatsApp click-to-chat: links with a pre-filled message and an optional floating button.
 *
 * Uses the public wa.me link format only. Nothing is sent anywhere until the
 * visitor clicks the link, which opens WhatsApp in a new tab or the app.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Frontend;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;
use Wbdres\RealEstate\Support\Property;

defined( 'ABSPATH' ) || exit;

/**
 * WhatsApp.
 */
final class WhatsApp {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
		add_action( 'wp_footer', array( $this, 'floating_button' ) );
	}

	/**
	 * Digits-only number for wa.me (country code, no "+" or spaces).
	 *
	 * @param string $number Raw phone.
	 * @return string
	 */
	public static function digits( $number ) {
		return preg_replace( '/\D/', '', (string) $number );
	}

	/**
	 * WhatsApp number for an agent, falling back to the site-wide number.
	 *
	 * @param \WP_Post|null $agent Agent post.
	 * @return string Digits only, or ''.
	 */
	public static function number_for( $agent = null ) {
		$number = '';
		if ( $agent instanceof \WP_Post ) {
			$number = (string) get_post_meta( $agent->ID, '_wbdres_agent_whatsapp', true );
		}
		if ( '' === self::digits( $number ) ) {
			$number = (string) Settings::get( 'whatsapp_number', '' );
		}
		$digits = self::digits( $number );
		return strlen( $digits ) >= 7 ? $digits : '';
	}

	/**
	 * Pre-filled message for a property.
	 *
	 * @param Property $p Property.
	 * @return string
	 */
	public static function property_message( Property $p ) {
		$template = (string) Settings::get( 'whatsapp_message', '' );
		$ref      = (string) $p->meta( 'reference' );
		$message  = strtr(
			$template,
			array(
				'{title}'     => wp_strip_all_tags( get_the_title( $p->id() ) ),
				'{url}'       => get_permalink( $p->id() ),
				'{reference}' => $ref,
				'{price}'     => wp_strip_all_tags( View::price_html( $p ) ),
			)
		);
		/**
		 * Filter the pre-filled WhatsApp message for a property.
		 *
		 * @param string   $message Message.
		 * @param Property $p       Property.
		 */
		return (string) apply_filters( 'wbdres_whatsapp_property_message', trim( preg_replace( '/[ \t]+/', ' ', $message ) ), $p );
	}

	/**
	 * Build a wa.me URL.
	 *
	 * @param string $digits  Number (digits).
	 * @param string $message Optional pre-filled text.
	 * @return string
	 */
	public static function url( $digits, $message = '' ) {
		$url = 'https://wa.me/' . self::digits( $digits );
		if ( '' !== $message ) {
			$url .= '?text=' . rawurlencode( $message );
		}
		return $url;
	}

	/**
	 * Button markup.
	 *
	 * @param string $digits  Number.
	 * @param string $message Pre-filled text.
	 * @param string $label   Visible label.
	 * @param string $class   Extra classes.
	 * @return string
	 */
	public static function button( $digits, $message = '', $label = '', $class = '' ) {
		if ( '' === $digits ) {
			return '';
		}
		$label = '' === $label ? __( 'WhatsApp', 'wbd-real-estate-suite' ) : $label;
		return '<a class="wbdres-button wbdres-button--whatsapp ' . esc_attr( $class ) . '" href="' . esc_url( self::url( $digits, $message ) ) . '" target="_blank" rel="noopener noreferrer">'
			. self::icon() . '<span>' . esc_html( $label ) . '</span><span class="screen-reader-text"> ' . esc_html__( '(opens WhatsApp)', 'wbd-real-estate-suite' ) . '</span></a>';
	}

	/**
	 * WhatsApp glyph (decorative).
	 *
	 * @return string
	 */
	public static function icon() {
		return '<svg class="wbdres-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.04 2a9.9 9.9 0 0 0-8.5 15l-1.4 5 5.2-1.36A9.9 9.9 0 1 0 12.04 2Zm0 18.1a8.2 8.2 0 0 1-4.2-1.15l-.3-.18-3.08.8.82-3-.2-.31a8.2 8.2 0 1 1 6.96 3.84Zm4.5-6.14c-.25-.12-1.46-.72-1.69-.8-.23-.08-.39-.12-.56.12-.16.25-.64.8-.78.97-.15.16-.29.18-.54.06a6.7 6.7 0 0 1-3.32-2.9c-.25-.43.25-.4.72-1.33.08-.16.04-.3-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.41-.56-.42h-.48a.92.92 0 0 0-.66.31 2.8 2.8 0 0 0-.87 2.07 4.85 4.85 0 0 0 1.02 2.58 11.1 11.1 0 0 0 4.25 3.76c1.58.68 2.2.74 2.99.62.48-.07 1.46-.6 1.67-1.18.2-.58.2-1.08.14-1.18-.06-.11-.22-.17-.47-.29Z"/></svg>';
	}

	/**
	 * Should the floating button show on this request?
	 *
	 * @return bool
	 */
	private static function float_enabled() {
		$mode = (string) Settings::get( 'whatsapp_float', 'off' );
		if ( 'off' === $mode || is_admin() || is_feed() || is_embed() ) {
			return false;
		}
		if ( 'plugin' === $mode && ! is_singular( array( Registrar::PROPERTY, Registrar::AGENT ) ) && ! Query::is_property_archive() ) {
			return false;
		}
		return true;
	}

	/**
	 * Load the stylesheet where the floating button renders.
	 */
	public function maybe_enqueue() {
		if ( self::float_enabled() ) {
			Assets::enqueue_frontend();
		}
	}

	/**
	 * Floating button (bottom corner).
	 */
	public function floating_button() {
		if ( ! self::float_enabled() ) {
			return;
		}
		$agent   = null;
		$message = '';
		if ( is_singular( Registrar::PROPERTY ) ) {
			$p = Property::get( get_queried_object() );
			if ( $p ) {
				$agent   = $p->agent();
				$message = self::property_message( $p );
			}
		} elseif ( is_singular( Registrar::AGENT ) ) {
			$agent = get_queried_object();
		}
		$digits = self::number_for( $agent instanceof \WP_Post ? $agent : null );
		if ( '' === $digits ) {
			return;
		}
		echo '<a class="wbdres-wa-float" href="' . esc_url( self::url( $digits, $message ) ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Chat with us on WhatsApp (opens WhatsApp)', 'wbd-real-estate-suite' ) . '">' . self::icon() . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
	}
}
