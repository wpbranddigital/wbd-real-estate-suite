<?php
/**
 * Frontend asset registration. Assets load only where plugin output renders.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Frontend;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Assets.
 */
final class Assets {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'init', array( __CLASS__, 'register_assets' ), 4 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_on_plugin_pages' ) );
	}

	/**
	 * Register handles used by block.json files and PHP renderers.
	 */
	public static function register_assets() {
		$style_asset = self::asset( 'frontend/style' );
		wp_register_style( 'wbdres-frontend', WBDRES_URL . 'build/frontend/style-style.css', array(), $style_asset['version'] );
		wp_style_add_data( 'wbdres-frontend', 'path', WBDRES_PATH . 'build/frontend/style-style.css' );
		wp_style_add_data( 'wbdres-frontend', 'rtl', 'replace' );

		$accent = (string) Settings::get( 'accent_color', '#0f766e' );
		wp_add_inline_style( 'wbdres-frontend', ':root{--wbdres-accent:' . sanitize_hex_color( $accent ) . ';}' );

		$view = self::asset( 'frontend/view' );
		wp_register_script_module( '@wbdres/view', WBDRES_URL . 'build/frontend/view.js', $view['dependencies'], $view['version'] );
	}

	/**
	 * Read a build asset file.
	 *
	 * @param string $name Entry name.
	 * @return array
	 */
	public static function asset( $name ) {
		$file = WBDRES_PATH . 'build/' . $name . '.asset.php';
		if ( is_readable( $file ) ) {
			$asset = include $file;
			if ( is_array( $asset ) ) {
				return wp_parse_args(
					$asset,
					array(
						'dependencies' => array(),
						'version'      => WBDRES_VERSION,
					)
				);
			}
		}
		return array(
			'dependencies' => array(),
			'version'      => WBDRES_VERSION,
		);
	}

	/**
	 * Enqueue stylesheet.
	 */
	public static function enqueue_frontend() {
		wp_enqueue_style( 'wbdres-frontend' );
	}

	/**
	 * Load styles on plugin-driven views (archives, singles).
	 */
	public function enqueue_on_plugin_pages() {
		if ( is_singular( array( Registrar::PROPERTY, Registrar::AGENT ) ) || Query::is_property_archive() ) {
			self::enqueue_frontend();
		}
	}
}
