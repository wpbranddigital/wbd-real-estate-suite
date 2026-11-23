<?php
/**
 * Uninstall: removes data only when "Delete all data on uninstall" is enabled.
 *
 * @package Wbdres\RealEstate
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove data for the current site.
 */
function wbdres_uninstall_site() {
	global $wpdb;
	$settings = get_option( 'wbdres_settings', array() );

	wp_clear_scheduled_hook( 'wbdres_daily_cleanup' );
	delete_option( 'wbdres_show_welcome' );

	if ( empty( $settings['delete_on_uninstall'] ) ) {
		return; // Keep listings, leads and settings.
	}

	// Posts (properties, agents) with their meta and attachments' parent links.
	$post_ids = get_posts(
		array(
			'post_type'      => array( 'wbd_property', 'wbd_agent' ),
			// 'any' skips trash and auto-draft, so list every status explicitly.
			'post_status'    => array_merge( array_keys( get_post_stati() ), array( 'trash', 'auto-draft', 'inherit' ) ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $post_ids as $post_id ) {
		wp_delete_post( $post_id, true );
	}

	// Terms.
	foreach ( array( 'wbd_property_type', 'wbd_property_status', 'wbd_property_location', 'wbd_property_feature' ) as $taxonomy ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Taxonomy is unregistered during uninstall.
		$terms = $wpdb->get_col( $wpdb->prepare( "SELECT t.term_id FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE tt.taxonomy = %s", $taxonomy ) );
		foreach ( $terms as $term_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->delete(
				$wpdb->term_taxonomy,
				array(
					'term_id'  => (int) $term_id,
					'taxonomy' => $taxonomy,
				),
				array( '%d', '%s' )
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->delete( $wpdb->termmeta, array( 'term_id' => (int) $term_id ), array( '%d' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->delete( $wpdb->terms, array( 'term_id' => (int) $term_id ), array( '%d' ) );
		}
	}

	// Custom tables.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery -- Removing our own tables on uninstall.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wbdres_lead_notes" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wbdres_leads" );
	// phpcs:enable

	// Options & transients.
	foreach ( array( 'wbdres_settings', 'wbdres_email_templates', 'wbdres_db_version', 'wbdres_installed_at', 'wbdres_import_batches', 'wbdres_demo_pages', 'wbdres_sample_installed', 'wbdres_flush_rewrite', 'wbdres_setup_done', 'wbdres_setup_preset', 'wbdres_setup_agent' ) as $option ) {
		delete_option( $option );
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_wbdres_' ) . '%', $wpdb->esc_like( '_transient_timeout_wbdres_' ) . '%' ) );

	// Roles & capabilities.
	$caps = array( 'wbdres_manage_leads', 'wbdres_view_own_leads', 'manage_wbdres' );
	foreach ( array(
		'wbd_property' => 'wbd_properties',
		'wbd_agent'    => 'wbd_agents',
	) as $plural ) {
		foreach ( array( 'edit_', 'edit_others_', 'edit_private_', 'edit_published_', 'publish_', 'read_private_', 'delete_', 'delete_private_', 'delete_published_', 'delete_others_' ) as $prefix ) {
			$caps[] = $prefix . $plural;
		}
	}
	foreach ( array( 'administrator', 'editor' ) as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $caps as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
	remove_role( 'wbdres_agent' );
}

if ( is_multisite() ) {
	$wbdres_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 500,
		)
	);
	foreach ( $wbdres_sites as $wbdres_site ) {
		switch_to_blog( $wbdres_site );
		wbdres_uninstall_site();
		restore_current_blog();
	}
} else {
	wbdres_uninstall_site();
}
