<?php
/**
 * Editable email templates.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Email;

defined( 'ABSPATH' ) || exit;

/**
 * Email template definitions and storage (option `wbdres_email_templates`).
 */
final class Templates {

	const OPTION = 'wbdres_email_templates';

	/**
	 * Event definitions with default subject/body.
	 *
	 * @return array
	 */
	public static function definitions() {
		return array(
			'lead_admin'         => array(
				'label'     => __( 'New lead — admin notification', 'wbd-real-estate-suite' ),
				'recipient' => __( 'Site admin + notification emails', 'wbd-real-estate-suite' ),
				'subject'   => __( '[{site_name}] New {lead_type} from {lead_name}', 'wbd-real-estate-suite' ),
				'body'      => __( "You have a new {lead_type}.\n\nName: {lead_name}\nEmail: {lead_email}\nPhone: {lead_phone}\nProperty: {property_title} {property_url}\nPreferred date: {preferred_date} {preferred_time}\n\nMessage:\n{lead_message}\n\nManage this lead: {lead_admin_url}", 'wbd-real-estate-suite' ),
			),
			'lead_agent'         => array(
				'label'     => __( 'New lead — agent notification', 'wbd-real-estate-suite' ),
				'recipient' => __( 'Agent linked to the property or profile', 'wbd-real-estate-suite' ),
				'subject'   => __( 'New {lead_type} for {property_title}', 'wbd-real-estate-suite' ),
				'body'      => __( "Hi {agent_name},\n\n{lead_name} sent a {lead_type}.\n\nEmail: {lead_email}\nPhone: {lead_phone}\nProperty: {property_title} {property_url}\nPreferred date: {preferred_date} {preferred_time}\n\nMessage:\n{lead_message}\n\nReply directly to this email to answer the client.", 'wbd-real-estate-suite' ),
			),
			'lead_autoreply'     => array(
				'label'     => __( 'Auto-reply to the visitor', 'wbd-real-estate-suite' ),
				'recipient' => __( 'Person who submitted the form', 'wbd-real-estate-suite' ),
				'subject'   => __( 'We received your message — {site_name}', 'wbd-real-estate-suite' ),
				'body'      => __( "Hi {lead_name},\n\nThank you for contacting {site_name}. We received your {lead_type} and will get back to you shortly.\n\nProperty: {property_title} {property_url}\n\nBest regards,\n{site_name}", 'wbd-real-estate-suite' ),
			),
			'lead_assigned'      => array(
				'label'     => __( 'Lead assigned to agent', 'wbd-real-estate-suite' ),
				'recipient' => __( 'Newly assigned agent', 'wbd-real-estate-suite' ),
				'subject'   => __( 'A lead was assigned to you: {lead_name}', 'wbd-real-estate-suite' ),
				'body'      => __( "Hi {agent_name},\n\nThe lead {lead_name} ({lead_email}, {lead_phone}) has been assigned to you.\nProperty: {property_title}\n\nOpen the lead: {lead_admin_url}", 'wbd-real-estate-suite' ),
			),
			'property_submitted' => array(
				'label'     => __( 'Property submitted — admin', 'wbd-real-estate-suite' ),
				'recipient' => __( 'Site admin + notification emails', 'wbd-real-estate-suite' ),
				'subject'   => __( '[{site_name}] New property awaiting review: {property_title}', 'wbd-real-estate-suite' ),
				'body'      => __( "{submitter_name} submitted a property for review.\n\nTitle: {property_title}\nReview it here: {property_edit_url}", 'wbd-real-estate-suite' ),
			),
			'property_approved'  => array(
				'label'     => __( 'Property approved — submitter', 'wbd-real-estate-suite' ),
				'recipient' => __( 'User who submitted the property', 'wbd-real-estate-suite' ),
				'subject'   => __( 'Your property is live: {property_title}', 'wbd-real-estate-suite' ),
				'body'      => __( "Hi {submitter_name},\n\nGood news — your property \"{property_title}\" has been approved and is now published:\n{property_url}\n\nThank you,\n{site_name}", 'wbd-real-estate-suite' ),
			),
			'property_rejected'  => array(
				'label'     => __( 'Property needs changes — submitter', 'wbd-real-estate-suite' ),
				'recipient' => __( 'User who submitted the property', 'wbd-real-estate-suite' ),
				'subject'   => __( 'Your property needs changes: {property_title}', 'wbd-real-estate-suite' ),
				'body'      => __( "Hi {submitter_name},\n\nYour property \"{property_title}\" could not be published yet.\n\nReason: {reason}\n\nPlease contact us if you have questions.\n{site_name}", 'wbd-real-estate-suite' ),
			),
		);
	}

	/**
	 * Placeholders reference.
	 *
	 * @return array
	 */
	public static function placeholders() {
		return array( '{site_name}', '{site_url}', '{lead_name}', '{lead_email}', '{lead_phone}', '{lead_message}', '{lead_type}', '{lead_status}', '{preferred_date}', '{preferred_time}', '{lead_admin_url}', '{property_title}', '{property_url}', '{property_edit_url}', '{agent_name}', '{submitter_name}', '{reason}' );
	}

	/**
	 * Get a template (stored or default).
	 *
	 * @param string $event Event.
	 * @return array{enabled: bool, subject: string, body: string}
	 */
	public static function get( $event ) {
		$defs   = self::definitions();
		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) && isset( $stored[ $event ] ) && is_array( $stored[ $event ] ) ? $stored[ $event ] : array();
		$def    = isset( $defs[ $event ] ) ? $defs[ $event ] : array(
			'subject' => '',
			'body'    => '',
		);
		return array(
			'enabled' => isset( $stored['enabled'] ) ? (bool) $stored['enabled'] : true,
			'subject' => ! empty( $stored['subject'] ) ? (string) $stored['subject'] : $def['subject'],
			'body'    => ! empty( $stored['body'] ) ? (string) $stored['body'] : $def['body'],
		);
	}

	/**
	 * Sanitize submitted templates.
	 *
	 * @param mixed $input Input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$out = array();
		foreach ( array_keys( self::definitions() ) as $event ) {
			$row           = isset( $input[ $event ] ) && is_array( $input[ $event ] ) ? $input[ $event ] : array();
			$out[ $event ] = array(
				'enabled' => empty( $row['enabled'] ) ? 0 : 1,
				'subject' => mb_substr( sanitize_text_field( (string) ( $row['subject'] ?? '' ) ), 0, 200 ),
				'body'    => mb_substr( sanitize_textarea_field( (string) ( $row['body'] ?? '' ) ), 0, 10000 ),
			);
		}
		return $out;
	}
}
