<?php
/**
 * Renders templates and sends mail through wp_mail (works with any SMTP plugin).
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Email;

defined( 'ABSPATH' ) || exit;

/**
 * Mailer.
 */
final class Mailer {

	/**
	 * Send an event email.
	 *
	 * @param string          $event    Event key.
	 * @param string|string[] $to       Recipient(s).
	 * @param array           $vars     Placeholder values (plain text).
	 * @param string          $reply_to Optional Reply-To email.
	 * @return bool
	 */
	public static function send( $event, $to, array $vars, $reply_to = '' ) {
		$tpl = Templates::get( $event );
		if ( ! $tpl['enabled'] ) {
			return false;
		}
		$to = array_values( array_filter( (array) $to, 'is_email' ) );
		if ( ! $to ) {
			return false;
		}
		$vars    = array_merge(
			array(
				'site_name' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				'site_url'  => home_url( '/' ),
			),
			$vars
		);
		$replace = array();
		foreach ( Templates::placeholders() as $ph ) {
			$key            = trim( $ph, '{}' );
			$replace[ $ph ] = isset( $vars[ $key ] ) ? (string) $vars[ $key ] : '';
		}
		$subject = wp_strip_all_tags( strtr( $tpl['subject'], $replace ) );
		$subject = preg_replace( '/[\r\n]+/', ' ', $subject );
		$text    = strtr( $tpl['body'], $replace );
		$html    = self::wrap( $subject, $text );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( $reply_to && is_email( $reply_to ) ) {
			$name      = isset( $vars['lead_name'] ) ? preg_replace( '/[^\p{L}\p{N} .\'-]/u', '', (string) $vars['lead_name'] ) : '';
			$headers[] = 'Reply-To: ' . ( $name ? $name . ' ' : '' ) . '<' . $reply_to . '>';
		}

		/**
		 * Filter outgoing email arguments.
		 *
		 * @param array  $args  to, subject, message, headers.
		 * @param string $event Event key.
		 * @param array  $vars  Variables.
		 */
		$args = apply_filters(
			'wbdres_email_args',
			array(
				'to'      => $to,
				'subject' => $subject,
				'message' => $html,
				'headers' => $headers,
			),
			$event,
			$vars
		);
		return (bool) wp_mail( $args['to'], $args['subject'], $args['message'], $args['headers'] );
	}

	/**
	 * Wrap plain text in a minimal, accessible HTML layout (links auto-linked, all text escaped).
	 *
	 * @param string $title Title.
	 * @param string $text  Body text.
	 * @return string
	 */
	public static function wrap( $title, $text ) {
		$body   = make_clickable( nl2br( esc_html( $text ) ) );
		$accent = sanitize_hex_color( (string) \Wbdres\RealEstate\Settings::get( 'accent_color', '#0f766e' ) );
		return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . esc_html( $title ) . '</title></head>'
			. '<body style="margin:0;background:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2937">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden">'
			. '<tr><td style="background:' . esc_attr( $accent ) . ';color:#ffffff;padding:18px 24px;font-size:16px;font-weight:600">' . esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) . '</td></tr>'
			. '<tr><td style="padding:24px;font-size:15px;line-height:1.6">' . $body . '</td></tr>'
			. '<tr><td style="padding:16px 24px;font-size:12px;color:#6b7280;border-top:1px solid #eef0f3">' . esc_html( home_url( '/' ) ) . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}
}
