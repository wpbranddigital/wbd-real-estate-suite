<?php
/**
 * One-time, dismissible welcome notice (shown only on plugin screens and Plugins page).
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Notices.
 */
final class Notices {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'admin_notices', array( $this, 'welcome' ) );
		add_action( 'admin_post_wbdres_dismiss_welcome', array( $this, 'dismiss' ) );
	}

	/**
	 * Welcome notice.
	 */
	public function welcome() {
		if ( ! get_option( 'wbdres_show_welcome' ) || ! current_user_can( 'manage_wbdres' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ( 'plugins' !== $screen->id && false === strpos( (string) $screen->id, 'wbdres' ) ) ) {
			return;
		}
		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=wbdres_dismiss_welcome' ), 'wbdres_dismiss_welcome' );
		echo '<div class="notice notice-info wbdres-welcome"><p><strong>' . esc_html__( 'Welcome to WBD Real Estate Suite!', 'wbd-real-estate-suite' ) . '</strong> ' . esc_html__( 'Set your currency, add an agent and your first property — or install demo content to explore.', 'wbd-real-estate-suite' ) . '</p><p><a class="button button-primary" href="' . esc_url( SetupWizard::url() ) . '">' . esc_html__( 'Run the 2-minute setup wizard', 'wbd-real-estate-suite' ) . '</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=wbdres' ) ) . '">' . esc_html__( 'Open overview', 'wbd-real-estate-suite' ) . '</a> <a href="' . esc_url( $dismiss ) . '">' . esc_html__( 'Dismiss', 'wbd-real-estate-suite' ) . '</a></p></div>';
	}

	/**
	 * Dismiss.
	 */
	public function dismiss() {
		check_admin_referer( 'wbdres_dismiss_welcome' );
		if ( current_user_can( 'manage_wbdres' ) ) {
			delete_option( 'wbdres_show_welcome' );
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
}
