<?php
/**
 * Admin menu: "Real Estate".
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\Frontend\Assets;
use Wbdres\RealEstate\Leads\LeadRepository;
use Wbdres\RealEstate\Leads\LeadService;
use Wbdres\RealEstate\PostTypes\Registrar;

defined( 'ABSPATH' ) || exit;

/**
 * Menu.
 */
final class Menu {

	/**
	 * Page hook suffixes.
	 *
	 * @var string[]
	 */
	private $hooks = array();

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'reorder' ), 99 );
		add_filter( 'parent_file', array( $this, 'parent_file' ) );
		add_filter( 'submenu_file', array( $this, 'submenu_file' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		( new LeadsPage() )->register();
		( new SettingsPage() )->register();
		( new ToolsPage() )->register();
		( new SetupWizard() )->register();
	}

	/**
	 * Build menu.
	 */
	public function menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M10 1.5 1.5 8v10.5h6v-6h5v6h6V8L10 1.5Zm0 2.3 6.5 5v7.7h-2v-6h-9v6h-2V8.8l6.5-5Z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- SVG menu icon.

		$this->hooks[] = add_menu_page(
			__( 'Real Estate', 'wbd-real-estate-suite' ),
			__( 'Real Estate', 'wbd-real-estate-suite' ),
			current_user_can( 'edit_wbd_properties' ) || LeadService::can_access() ? 'read' : 'do_not_allow',
			'wbdres',
			array( new DashboardPage(), 'render' ),
			$icon,
			26
		);
		// Same slug as the parent: no callback, otherwise the page would render twice.
		add_submenu_page( 'wbdres', __( 'Overview', 'wbd-real-estate-suite' ), __( 'Overview', 'wbd-real-estate-suite' ), 'read', 'wbdres' );
		add_submenu_page( 'wbdres', __( 'Add New Property', 'wbd-real-estate-suite' ), __( 'Add New Property', 'wbd-real-estate-suite' ), 'edit_wbd_properties', 'post-new.php?post_type=' . Registrar::PROPERTY );

		foreach ( array(
			Registrar::TYPE     => __( 'Property Types', 'wbd-real-estate-suite' ),
			Registrar::STATUS   => __( 'Property Statuses', 'wbd-real-estate-suite' ),
			Registrar::LOCATION => __( 'Locations', 'wbd-real-estate-suite' ),
			Registrar::FEATURE  => __( 'Features & Amenities', 'wbd-real-estate-suite' ),
		) as $taxonomy => $label ) {
			add_submenu_page( 'wbdres', $label, $label, 'manage_categories', 'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=' . Registrar::PROPERTY );
		}

		$counts = LeadService::can_access() ? LeadRepository::counts( LeadService::scope() ) : array();
		$new    = isset( $counts['new'] ) ? (int) $counts['new'] : 0;
		$bubble = $new ? ' <span class="awaiting-mod count-' . esc_attr( $new ) . '"><span class="pending-count">' . esc_html( number_format_i18n( $new ) ) . '</span></span>' : '';

		$this->hooks[] = add_submenu_page( 'wbdres', __( 'Leads', 'wbd-real-estate-suite' ), __( 'Leads', 'wbd-real-estate-suite' ) . $bubble, LeadService::can_access() ? 'read' : 'do_not_allow', 'wbdres-leads', array( new LeadsPage(), 'render' ) );
		$this->hooks[] = add_submenu_page( 'wbdres', __( 'Analytics', 'wbd-real-estate-suite' ), __( 'Analytics', 'wbd-real-estate-suite' ), current_user_can( 'wbdres_manage_leads' ) ? 'read' : 'do_not_allow', 'wbdres-analytics', array( new AnalyticsPage(), 'render' ) );
		$this->hooks[] = add_submenu_page( 'wbdres', __( 'Import / Export & Tools', 'wbd-real-estate-suite' ), __( 'Tools', 'wbd-real-estate-suite' ), 'manage_wbdres', 'wbdres-tools', array( new ToolsPage(), 'render' ) );
		$this->hooks[] = add_submenu_page( 'wbdres', __( 'Settings', 'wbd-real-estate-suite' ), __( 'Settings', 'wbd-real-estate-suite' ), 'manage_wbdres', 'wbdres-settings', array( new SettingsPage(), 'render' ) );
		$this->hooks[] = add_submenu_page( 'wbdres', __( 'Setup Wizard', 'wbd-real-estate-suite' ), __( 'Setup Wizard', 'wbd-real-estate-suite' ), 'manage_wbdres', SetupWizard::SLUG, array( new SetupWizard(), 'render' ) );
	}

	/**
	 * Order: Overview, All Properties, Add New, taxonomies, Agents, Leads, Analytics, Tools, Settings.
	 */
	public function reorder() {
		global $submenu;
		if ( empty( $submenu['wbdres'] ) ) {
			return;
		}
		$order = array(
			'wbdres',
			'edit.php?post_type=' . Registrar::PROPERTY,
			'post-new.php?post_type=' . Registrar::PROPERTY,
			'edit-tags.php?taxonomy=' . Registrar::TYPE . '&post_type=' . Registrar::PROPERTY,
			'edit-tags.php?taxonomy=' . Registrar::STATUS . '&post_type=' . Registrar::PROPERTY,
			'edit-tags.php?taxonomy=' . Registrar::LOCATION . '&post_type=' . Registrar::PROPERTY,
			'edit-tags.php?taxonomy=' . Registrar::FEATURE . '&post_type=' . Registrar::PROPERTY,
			'edit.php?post_type=' . Registrar::AGENT,
			'wbdres-leads',
			'wbdres-analytics',
			'wbdres-tools',
			'wbdres-settings',
			SetupWizard::SLUG,
		);
		$items = $submenu['wbdres'];
		usort(
			$items,
			static function ( $a, $b ) use ( $order ) {
				$ia = array_search( $a[2], $order, true );
				$ib = array_search( $b[2], $order, true );
				$ia = false === $ia ? 50 : $ia;
				$ib = false === $ib ? 50 : $ib;
				return $ia <=> $ib;
			}
		);
		$submenu['wbdres'] = $items; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reordering our own submenu.
	}

	/**
	 * Highlight our menu on taxonomy and CPT screens.
	 *
	 * @param string $parent_file Parent.
	 * @return string
	 */
	public function parent_file( $parent_file ) {
		$screen = get_current_screen();
		if ( $screen && ( in_array( $screen->post_type, array( Registrar::PROPERTY, Registrar::AGENT ), true ) || in_array( $screen->taxonomy, array( Registrar::TYPE, Registrar::STATUS, Registrar::LOCATION, Registrar::FEATURE ), true ) ) ) {
			return 'wbdres';
		}
		return $parent_file;
	}

	/**
	 * Highlight submenu.
	 *
	 * @param string|null $submenu_file Submenu.
	 * @return string|null
	 */
	public function submenu_file( $submenu_file ) {
		$screen = get_current_screen();
		if ( $screen && $screen->taxonomy && in_array( $screen->taxonomy, array( Registrar::TYPE, Registrar::STATUS, Registrar::LOCATION, Registrar::FEATURE ), true ) ) {
			return 'edit-tags.php?taxonomy=' . $screen->taxonomy . '&post_type=' . Registrar::PROPERTY;
		}
		return $submenu_file;
	}

	/**
	 * Admin assets on our screens only.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function assets( $hook ) {
		$screen = get_current_screen();
		$ours   = in_array( $hook, $this->hooks, true ) || ( $screen && in_array( $screen->post_type, array( Registrar::PROPERTY, Registrar::AGENT ), true ) );
		if ( ! $ours ) {
			return;
		}
		$asset = Assets::asset( 'admin/admin' );
		wp_enqueue_style( 'wbdres-admin', WBDRES_URL . 'build/admin/admin.css', array(), $asset['version'] );
		wp_style_add_data( 'wbdres-admin', 'rtl', 'replace' );
		wp_enqueue_script( 'wbdres-admin', WBDRES_URL . 'build/admin/admin.js', $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
		wp_localize_script(
			'wbdres-admin',
			'wbdresAdmin',
			array(
				'confirmDelete' => __( 'Delete the selected leads permanently? This cannot be undone.', 'wbd-real-estate-suite' ),
				'confirmUndo'   => __( 'Delete every property created by this import? This cannot be undone.', 'wbd-real-estate-suite' ),
				'confirmSample' => __( 'Remove all sample content?', 'wbd-real-estate-suite' ),
			)
		);
	}
}
