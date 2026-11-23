<?php
/**
 * Activation, deactivation, schema creation and migrations.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Install;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Installer.
 */
final class Installer {

	const DB_OPTION = 'wbdres_db_version';

	/**
	 * Activation callback.
	 *
	 * @param bool $network_wide Network activation flag.
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$sites = get_sites(
				array(
					'fields' => 'ids',
					'number' => 200,
				)
			);
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				self::install_site();
				restore_current_blog();
			}
			return;
		}
		self::install_site();
	}

	/**
	 * Install for the current site.
	 */
	private static function install_site() {
		self::create_tables();
		Roles::add();

		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::defaults(), '', false );
		}
		add_option( 'wbdres_installed_at', time(), '', false );

		( new Registrar() )->register_post_types();
		( new Registrar() )->register_taxonomies();
		Registrar::insert_default_terms();
		flush_rewrite_rules();

		if ( ! wp_next_scheduled( 'wbdres_daily_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wbdres_daily_cleanup' );
		}
		update_option( 'wbdres_show_welcome', 1, false );
	}

	/**
	 * Deactivation callback. Data is kept; only scheduled events and rewrite rules are cleared.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'wbdres_daily_cleanup' );
		flush_rewrite_rules();
	}

	/**
	 * Run schema migrations when the stored DB version is older than the code.
	 */
	public static function maybe_upgrade() {
		$installed = get_option( self::DB_OPTION, '0' );
		if ( version_compare( $installed, WBDRES_DB_VERSION, '>=' ) ) {
			return;
		}
		self::create_tables();
		Roles::add();
		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::defaults(), '', false );
		}

		/*
		 * Future data migrations go here, keyed by version:
		 * if ( version_compare( $installed, '1.1.0', '<' ) ) { self::migrate_110(); }
		 */
	}

	/**
	 * Lead table name.
	 *
	 * @return string
	 */
	public static function leads_table() {
		global $wpdb;
		return $wpdb->prefix . 'wbdres_leads';
	}

	/**
	 * Lead notes table name.
	 *
	 * @return string
	 */
	public static function notes_table() {
		global $wpdb;
		return $wpdb->prefix . 'wbdres_lead_notes';
	}

	/**
	 * Create or update custom tables with dbDelta.
	 *
	 * Only leads and their activity live in custom tables: they are private,
	 * high-volume, filtered by status/date, and must never appear in public
	 * post queries, search or sitemaps. Everything else uses posts, terms and meta.
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$leads   = self::leads_table();
		$notes   = self::notes_table();

		$sql = "CREATE TABLE {$leads} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  property_id bigint(20) unsigned NOT NULL DEFAULT 0,
  agent_id bigint(20) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  type varchar(20) NOT NULL DEFAULT 'inquiry',
  status varchar(20) NOT NULL DEFAULT 'new',
  name varchar(191) NOT NULL DEFAULT '',
  email varchar(191) NOT NULL DEFAULT '',
  phone varchar(50) NOT NULL DEFAULT '',
  message text NULL,
  preferred_date date NULL DEFAULT NULL,
  preferred_time varchar(20) NOT NULL DEFAULT '',
  source_url varchar(500) NOT NULL DEFAULT '',
  ip_hash char(64) NOT NULL DEFAULT '',
  consent tinyint(1) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY status_created (status,created_at),
  KEY property_id (property_id),
  KEY agent_id (agent_id),
  KEY email (email),
  KEY created_at (created_at)
) {$charset};
CREATE TABLE {$notes} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL DEFAULT 0,
  author_id bigint(20) unsigned NOT NULL DEFAULT 0,
  type varchar(20) NOT NULL DEFAULT 'note',
  note text NULL,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY lead_id (lead_id)
) {$charset};";

		dbDelta( $sql );
		// Autoloaded: maybe_upgrade() reads this on every request.
		update_option( self::DB_OPTION, WBDRES_DB_VERSION, true );
	}
}
