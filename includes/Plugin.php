<?php
/**
 * Main plugin orchestrator.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate;

defined( 'ABSPATH' ) || exit;

/**
 * Boots every component once (singleton).
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the shared instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up components.
	 */
	private function __construct() {
		Install\Installer::maybe_upgrade();

		( new PostTypes\Registrar() )->register();
		( new Meta\PropertyMeta() )->register();
		( new Meta\AgentMeta() )->register();
		( new Rest\Routes() )->register();
		( new Blocks\Blocks() )->register();
		( new Patterns\Patterns() )->register();
		( new Frontend\Assets() )->register();
		( new Frontend\Templates() )->register();
		( new Frontend\Query() )->register();
		( new Frontend\Forms() )->register();
		( new Frontend\WhatsApp() )->register();
		( new Schema\StructuredData() )->register();
		( new Privacy\Privacy() )->register();
		( new Privacy\Retention() )->register();
		( new Email\Notifications() )->register();

		if ( is_admin() ) {
			( new Admin\Menu() )->register();
			( new Admin\Columns() )->register();
			( new Admin\EditorAssets() )->register();
			( new Admin\Notices() )->register();
		}

		add_filter( 'plugin_action_links_' . WBDRES_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Add a Settings shortcut on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wbdres-settings' ) ),
			esc_html__( 'Settings', 'wbd-real-estate-suite' )
		);
		array_unshift( $links, $settings );
		return $links;
	}

	/**
	 * No cloning.
	 */
	private function __clone() {}

	/**
	 * No unserializing.
	 *
	 * @throws \Exception Always.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}
}
