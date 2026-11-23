<?php
/**
 * Daily cleanup: lead retention policy and expired transients.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Privacy;

use Wbdres\RealEstate\Leads\LeadRepository;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Retention.
 */
final class Retention {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'wbdres_daily_cleanup', array( $this, 'run' ) );
		add_action(
			'init',
			static function () {
				if ( ! wp_next_scheduled( 'wbdres_daily_cleanup' ) ) {
					wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wbdres_daily_cleanup' );
				}
			}
		);
	}

	/**
	 * Delete leads older than the configured number of days (0 = keep forever).
	 */
	public function run() {
		$days = (int) Settings::get( 'lead_retention_days', 0 );
		if ( $days > 0 ) {
			LeadRepository::purge_older_than( $days );
		}
	}
}
