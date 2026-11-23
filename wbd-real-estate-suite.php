<?php
/**
 * Plugin Name:       WBD Real Estate Suite
 * Description:       Lightweight, Gutenberg-native property listings, agent profiles, property search, inquiry forms and a built-in lead CRM for real estate agents and agencies.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            WPBrand Digital
 * Author URI:        https://www.wpbranddigital.org/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wbd-real-estate-suite
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;

define( 'WBDRES_VERSION', '1.0.0' );
define( 'WBDRES_DB_VERSION', '1.0.0' );
define( 'WBDRES_FILE', __FILE__ );
define( 'WBDRES_PATH', plugin_dir_path( __FILE__ ) );
define( 'WBDRES_URL', plugin_dir_url( __FILE__ ) );
define( 'WBDRES_BASENAME', plugin_basename( __FILE__ ) );

/**
 * PSR-4 autoloader for the Wbdres\RealEstate namespace.
 *
 * @param string $class_name Fully-qualified class name.
 */
spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'Wbdres\\RealEstate\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = WBDRES_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'Wbdres\\RealEstate\\Install\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Wbdres\\RealEstate\\Install\\Installer', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\Wbdres\RealEstate\Plugin::instance();
	}
);
