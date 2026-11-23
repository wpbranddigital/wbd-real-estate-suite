<?php
/**
 * Block editor sidebar panels for property and agent details.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\Frontend\Assets;
use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * EditorAssets.
 */
final class EditorAssets {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue sidebar for our post types; shared editor data for blocks everywhere.
	 */
	public function enqueue() {
		$asset = Assets::asset( 'editor/sidebar' );
		wp_enqueue_script( 'wbdres-editor-sidebar', WBDRES_URL . 'build/editor/sidebar.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_style( 'wbdres-editor-sidebar', WBDRES_URL . 'build/editor/sidebar.css', array( 'wp-components' ), $asset['version'] );
		wp_style_add_data( 'wbdres-editor-sidebar', 'rtl', 'replace' );
		wp_set_script_translations( 'wbdres-editor-sidebar', 'wbd-real-estate-suite' );
		$units = Settings::area_units();
		wp_add_inline_script(
			'wbdres-editor-sidebar',
			'window.wbdresEditor = ' . wp_json_encode(
				array(
					'propertyType' => Registrar::PROPERTY,
					'agentType'    => Registrar::AGENT,
					'currency'     => Settings::get( 'currency_symbol', '$' ),
					'currencyCode' => Settings::get( 'currency_code', 'USD' ),
					'areaUnit'     => $units[ Settings::get( 'area_unit', 'sqft' ) ] ?? 'sq ft',
					'canUsers'     => current_user_can( 'list_users' ),
				)
			) . ';',
			'before'
		);
	}
}
