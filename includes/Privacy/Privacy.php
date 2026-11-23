<?php
/**
 * GDPR tools: personal data exporter/eraser and privacy policy suggestion.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Privacy;

use Wbdres\RealEstate\Leads\LeadRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Privacy integration.
 */
final class Privacy {

	/**
	 * Hooks.
	 */
	public function register() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erasers' ) );
		add_action( 'admin_init', array( $this, 'policy_content' ) );
	}

	/**
	 * Register exporter.
	 *
	 * @param array $exporters Exporters.
	 * @return array
	 */
	public function exporters( $exporters ) {
		$exporters['wbd-real-estate-suite'] = array(
			'exporter_friendly_name' => __( 'Real Estate inquiries', 'wbd-real-estate-suite' ),
			'callback'               => array( $this, 'export' ),
		);
		return $exporters;
	}

	/**
	 * Register eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public function erasers( $erasers ) {
		$erasers['wbd-real-estate-suite'] = array(
			'eraser_friendly_name' => __( 'Real Estate inquiries', 'wbd-real-estate-suite' ),
			'callback'             => array( $this, 'erase' ),
		);
		return $erasers;
	}

	/**
	 * Export leads for an email.
	 *
	 * @param string $email Email.
	 * @param int    $page  Page.
	 * @return array
	 */
	public function export( $email, $page = 1 ) {
		$result = LeadRepository::query(
			array(
				'email'    => sanitize_email( $email ),
				'per_page' => 100,
				'page'     => (int) $page,
			)
		);
		$items  = array();
		foreach ( $result['items'] as $lead ) {
			$items[] = array(
				'group_id'    => 'wbdres-leads',
				'group_label' => __( 'Real estate inquiries', 'wbd-real-estate-suite' ),
				'item_id'     => 'wbdres-lead-' . (int) $lead->id,
				'data'        => array(
					array(
						'name'  => __( 'Date', 'wbd-real-estate-suite' ),
						'value' => $lead->created_at,
					),
					array(
						'name'  => __( 'Type', 'wbd-real-estate-suite' ),
						'value' => $lead->type,
					),
					array(
						'name'  => __( 'Name', 'wbd-real-estate-suite' ),
						'value' => $lead->name,
					),
					array(
						'name'  => __( 'Email', 'wbd-real-estate-suite' ),
						'value' => $lead->email,
					),
					array(
						'name'  => __( 'Phone', 'wbd-real-estate-suite' ),
						'value' => $lead->phone,
					),
					array(
						'name'  => __( 'Message', 'wbd-real-estate-suite' ),
						'value' => $lead->message,
					),
					array(
						'name'  => __( 'Property', 'wbd-real-estate-suite' ),
						'value' => $lead->property_id ? get_the_title( (int) $lead->property_id ) : '',
					),
				),
			);
		}
		return array(
			'data' => $items,
			'done' => ( (int) $page * 100 ) >= $result['total'],
		);
	}

	/**
	 * Erase leads for an email.
	 *
	 * @param string $email Email.
	 * @param int    $page  Page.
	 * @return array
	 */
	public function erase( $email, $page = 1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Signature required by core.
		$result  = LeadRepository::query(
			array(
				'email'    => sanitize_email( $email ),
				'per_page' => 100,
			)
		);
		$ids     = wp_list_pluck( $result['items'], 'id' );
		$removed = $ids ? LeadRepository::delete( $ids ) : 0;
		return array(
			'items_removed'  => $removed > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => $result['total'] <= 100,
		);
	}

	/**
	 * Suggested privacy policy text.
	 */
	public function policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$content = '<p class="privacy-policy-tutorial">' . esc_html__( 'Suggested text for sites using WBD Real Estate Suite:', 'wbd-real-estate-suite' ) . '</p>'
			. '<p>' . esc_html__( 'When you send an inquiry, viewing request or contact message through a property or agent form, we store your name, email address, phone number (if provided), message, the related property and the date. We use this data only to respond to your request. Your IP address is not stored; a one-way hash is kept temporarily to prevent spam.', 'wbd-real-estate-suite' ) . '</p>'
			. '<p>' . esc_html__( 'Saved favorites, comparisons and recently viewed properties are stored only in your own browser (local storage) and are not sent to us.', 'wbd-real-estate-suite' ) . '</p>'
			. '<p>' . esc_html__( 'Property maps are provided by OpenStreetMap and load only after you click "Load map". When loaded, your browser connects to openstreetmap.org (see the OpenStreetMap Foundation privacy policy).', 'wbd-real-estate-suite' ) . '</p>'
			. '<p>' . esc_html__( 'You can request an export or erasure of your inquiry data at any time.', 'wbd-real-estate-suite' ) . '</p>';
		wp_add_privacy_policy_content( 'WBD Real Estate Suite', wp_kses_post( $content ) );
	}
}
