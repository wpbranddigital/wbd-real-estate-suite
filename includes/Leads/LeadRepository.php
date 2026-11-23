<?php
/**
 * Lead persistence (custom tables, prepared statements only).
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Leads;

use Wbdres\RealEstate\Install\Installer;

defined( 'ABSPATH' ) || exit;

/**
 * CRUD for leads and lead notes.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery -- Custom tables; object cache group used where useful.
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names come from $wpdb->prefix.
 */
final class LeadRepository {

	/**
	 * Lead statuses (pipeline order).
	 *
	 * @return array
	 */
	public static function statuses() {
		return array(
			'new'         => __( 'New', 'wbd-real-estate-suite' ),
			'contacted'   => __( 'Contacted', 'wbd-real-estate-suite' ),
			'qualified'   => __( 'Qualified', 'wbd-real-estate-suite' ),
			'viewing'     => __( 'Viewing scheduled', 'wbd-real-estate-suite' ),
			'negotiation' => __( 'Negotiation', 'wbd-real-estate-suite' ),
			'won'         => __( 'Won', 'wbd-real-estate-suite' ),
			'lost'        => __( 'Lost', 'wbd-real-estate-suite' ),
		);
	}

	/**
	 * Lead types.
	 *
	 * @return array
	 */
	public static function types() {
		return array(
			'inquiry' => __( 'Property inquiry', 'wbd-real-estate-suite' ),
			'viewing' => __( 'Viewing request', 'wbd-real-estate-suite' ),
			'contact' => __( 'Agent contact', 'wbd-real-estate-suite' ),
			'manual'  => __( 'Added manually', 'wbd-real-estate-suite' ),
		);
	}

	/**
	 * Insert a lead.
	 *
	 * @param array $data Sanitized data.
	 * @return int Lead ID or 0.
	 */
	public static function insert( array $data ) {
		global $wpdb;
		$now     = current_time( 'mysql', true );
		$row     = array(
			'property_id'    => absint( $data['property_id'] ?? 0 ),
			'agent_id'       => absint( $data['agent_id'] ?? 0 ),
			'user_id'        => absint( $data['user_id'] ?? 0 ),
			'type'           => sanitize_key( $data['type'] ?? 'inquiry' ),
			'status'         => sanitize_key( $data['status'] ?? 'new' ),
			'name'           => (string) ( $data['name'] ?? '' ),
			'email'          => (string) ( $data['email'] ?? '' ),
			'phone'          => (string) ( $data['phone'] ?? '' ),
			'message'        => (string) ( $data['message'] ?? '' ),
			'preferred_date' => ! empty( $data['preferred_date'] ) ? $data['preferred_date'] : null,
			'preferred_time' => (string) ( $data['preferred_time'] ?? '' ),
			'source_url'     => (string) ( $data['source_url'] ?? '' ),
			'ip_hash'        => (string) ( $data['ip_hash'] ?? '' ),
			'consent'        => empty( $data['consent'] ) ? 0 : 1,
			'created_at'     => $now,
			'updated_at'     => $now,
		);
		$formats = array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' );
		if ( null === $row['preferred_date'] ) {
			unset( $row['preferred_date'] );
			unset( $formats[9] );
			$formats = array_values( $formats );
		}
		$ok = $wpdb->insert( Installer::leads_table(), $row, $formats );
		wp_cache_delete( 'counts', 'wbdres_leads' );
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Get one lead.
	 *
	 * @param int $id ID.
	 * @return object|null
	 */
	public static function find( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', Installer::leads_table(), absint( $id ) ) );
	}

	/**
	 * Update fields.
	 *
	 * @param int   $id     ID.
	 * @param array $fields Allowed: status, agent_id, name, email, phone, message.
	 * @return bool
	 */
	public static function update( $id, array $fields ) {
		global $wpdb;
		$allowed = array(
			'status'   => '%s',
			'agent_id' => '%d',
			'name'     => '%s',
			'email'    => '%s',
			'phone'    => '%s',
			'message'  => '%s',
		);
		$data    = array();
		$formats = array();
		foreach ( $fields as $k => $v ) {
			if ( isset( $allowed[ $k ] ) ) {
				$data[ $k ] = $v;
				$formats[]  = $allowed[ $k ];
			}
		}
		if ( ! $data ) {
			return false;
		}
		$data['updated_at'] = current_time( 'mysql', true );
		$formats[]          = '%s';
		wp_cache_delete( 'counts', 'wbdres_leads' );
		return false !== $wpdb->update( Installer::leads_table(), $data, array( 'id' => absint( $id ) ), $formats, array( '%d' ) );
	}

	/**
	 * Delete leads and their notes.
	 *
	 * @param int[] $ids IDs.
	 * @return int Deleted count.
	 */
	public static function delete( array $ids ) {
		global $wpdb;
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( ! $ids ) {
			return 0;
		}
		$in = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE lead_id IN ({$in})", array_merge( array( Installer::notes_table() ), $ids ) ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $in holds only %d placeholders.
		$count = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE id IN ({$in})", array_merge( array( Installer::leads_table() ), $ids ) ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $in holds only %d placeholders.
		wp_cache_delete( 'counts', 'wbdres_leads' );
		return $count;
	}

	/**
	 * Query leads.
	 *
	 * @param array $args status, type, agent_id, agent_ids, property_id, search, email, orderby, order, per_page, page, since, until.
	 * @return array{items: object[], total: int}
	 */
	public static function query( array $args = array() ) {
		global $wpdb;
		$a     = wp_parse_args(
			$args,
			array(
				'status'      => '',
				'type'        => '',
				'agent_id'    => null,
				'agent_ids'   => null,
				'property_id' => 0,
				'search'      => '',
				'email'       => '',
				'since'       => '',
				'orderby'     => 'created_at',
				'order'       => 'DESC',
				'per_page'    => 20,
				'page'        => 1,
			)
		);
		$table = Installer::leads_table();
		$where = array( '1=1' );
		$vals  = array();

		if ( $a['status'] && array_key_exists( $a['status'], self::statuses() ) ) {
			$where[] = 'status = %s';
			$vals[]  = $a['status'];
		}
		if ( $a['type'] && array_key_exists( $a['type'], self::types() ) ) {
			$where[] = 'type = %s';
			$vals[]  = $a['type'];
		}
		if ( null !== $a['agent_id'] && '' !== $a['agent_id'] ) {
			$where[] = 'agent_id = %d';
			$vals[]  = absint( $a['agent_id'] );
		}
		if ( is_array( $a['agent_ids'] ) ) {
			$ids = array_filter( array_map( 'absint', $a['agent_ids'] ) );
			if ( ! $ids ) {
				return array(
					'items' => array(),
					'total' => 0,
				);
			}
			$where[] = 'agent_id IN (' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')';
			$vals    = array_merge( $vals, $ids );
		}
		if ( $a['property_id'] ) {
			$where[] = 'property_id = %d';
			$vals[]  = absint( $a['property_id'] );
		}
		if ( $a['email'] ) {
			$where[] = 'email = %s';
			$vals[]  = $a['email'];
		}
		if ( $a['since'] ) {
			$where[] = 'created_at >= %s';
			$vals[]  = $a['since'];
		}
		if ( '' !== $a['search'] ) {
			$like    = '%' . $wpdb->esc_like( $a['search'] ) . '%';
			$where[] = '(name LIKE %s OR email LIKE %s OR phone LIKE %s OR message LIKE %s)';
			array_push( $vals, $like, $like, $like, $like );
		}

		$orderby  = in_array( $a['orderby'], array( 'created_at', 'name', 'status', 'type', 'updated_at' ), true ) ? $a['orderby'] : 'created_at';
		$order    = 'ASC' === strtoupper( (string) $a['order'] ) ? 'ASC' : 'DESC';
		$per_page = min( 500, max( 1, (int) $a['per_page'] ) );
		$offset   = ( max( 1, (int) $a['page'] ) - 1 ) * $per_page;
		$sql_w    = implode( ' AND ', $where );

		// $sql_w only contains fixed column conditions with %s/%d placeholders and $order is the
		// literal ASC or DESC. The table and the (whitelisted) order column are bound with %i.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql_w is built only from the fixed conditions above; every value is bound by prepare().
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE {$sql_w}", array_merge( array( $table ), $vals ) ) );
		$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE {$sql_w} ORDER BY %i {$order}, id DESC LIMIT %d OFFSET %d", array_merge( array( $table ), $vals, array( $orderby, $per_page, $offset ) ) ) );
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array(
			'items' => $items ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Counts per status (optionally limited to agent IDs).
	 *
	 * @param int[]|null $agent_ids Agent IDs.
	 * @return array status => count, plus 'all'.
	 */
	public static function counts( $agent_ids = null ) {
		global $wpdb;
		$table = Installer::leads_table();
		if ( null === $agent_ids ) {
			$cached = wp_cache_get( 'counts', 'wbdres_leads' );
			if ( false !== $cached ) {
				return $cached;
			}
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS c FROM %i GROUP BY status', $table ) );
		} else {
			$ids = array_filter( array_map( 'absint', (array) $agent_ids ) );
			if ( ! $ids ) {
				return array( 'all' => 0 );
			}
			$in   = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) AS c FROM %i WHERE agent_id IN ({$in}) GROUP BY status", array_merge( array( $table ), array_values( $ids ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $in holds only %d placeholders.
		}
		$out = array( 'all' => 0 );
		foreach ( (array) $rows as $r ) {
			$out[ $r->status ] = (int) $r->c;
			$out['all']       += (int) $r->c;
		}
		if ( null === $agent_ids ) {
			wp_cache_set( 'counts', $out, 'wbdres_leads', 5 * MINUTE_IN_SECONDS );
		}
		return $out;
	}

	/**
	 * Aggregate helpers for dashboards.
	 *
	 * @param string $group  Column: type|status|month.
	 * @param string $since  UTC datetime lower bound.
	 * @param string $until  UTC datetime upper bound (optional).
	 * @return array key => count
	 */
	public static function aggregate( $group, $since, $until = '' ) {
		global $wpdb;
		$table = Installer::leads_table();
		$until = $until ? $until : current_time( 'mysql', true );
		switch ( $group ) {
			case 'month':
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT DATE_FORMAT(created_at, '%%Y-%%m') AS k, COUNT(*) AS c FROM %i WHERE created_at >= %s AND created_at <= %s GROUP BY k ORDER BY k", $table, $since, $until ) );
				break;
			case 'day':
				$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT DATE(created_at) AS k, COUNT(*) AS c FROM %i WHERE created_at >= %s AND created_at <= %s GROUP BY k ORDER BY k', $table, $since, $until ) );
				break;
			case 'property':
				$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT property_id AS k, COUNT(*) AS c FROM %i WHERE property_id > 0 AND created_at >= %s AND created_at <= %s GROUP BY property_id ORDER BY c DESC LIMIT 10', $table, $since, $until ) );
				break;
			case 'type':
				$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT type AS k, COUNT(*) AS c FROM %i WHERE created_at >= %s AND created_at <= %s GROUP BY type', $table, $since, $until ) );
				break;
			default:
				$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status AS k, COUNT(*) AS c FROM %i WHERE created_at >= %s AND created_at <= %s GROUP BY status', $table, $since, $until ) );
		}
		$out = array();
		foreach ( (array) $rows as $r ) {
			$out[ (string) $r->k ] = (int) $r->c;
		}
		return $out;
	}

	/**
	 * Count leads between two dates.
	 *
	 * @param string $since Since (UTC).
	 * @param string $until Until (UTC).
	 * @return int
	 */
	public static function count_between( $since, $until ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE created_at >= %s AND created_at < %s', Installer::leads_table(), $since, $until ) );
	}

	/**
	 * Upcoming viewing requests.
	 *
	 * @param int        $limit     Limit.
	 * @param int[]|null $agent_ids Restrict to these agent IDs (null = all).
	 * @return object[]
	 */
	public static function upcoming_viewings( $limit = 5, $agent_ids = null ) {
		global $wpdb;
		$table = Installer::leads_table();
		$today = current_time( 'Y-m-d' );
		if ( ! is_array( $agent_ids ) ) {
			return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE type = 'viewing' AND preferred_date >= %s AND status NOT IN ('lost','won') ORDER BY preferred_date ASC LIMIT %d", $table, $today, absint( $limit ) ) );
		}
		$ids = array_values( array_filter( array_map( 'absint', $agent_ids ) ) );
		if ( ! $ids ) {
			return array();
		}
		// FIND_IN_SET with a bound "1,2,3" string keeps the SQL fully static.
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE type = 'viewing' AND preferred_date >= %s AND FIND_IN_SET(agent_id, %s) AND status NOT IN ('lost','won') ORDER BY preferred_date ASC LIMIT %d", $table, $today, implode( ',', $ids ), absint( $limit ) ) );
	}

	/**
	 * Add a note / activity entry.
	 *
	 * @param int    $lead_id Lead.
	 * @param string $note    Text.
	 * @param string $type    note|status|assign|email|system.
	 * @return int
	 */
	public static function add_note( $lead_id, $note, $type = 'note' ) {
		global $wpdb;
		$ok = $wpdb->insert(
			Installer::notes_table(),
			array(
				'lead_id'    => absint( $lead_id ),
				'author_id'  => get_current_user_id(),
				'type'       => sanitize_key( $type ),
				'note'       => (string) $note,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Notes for a lead (newest first).
	 *
	 * @param int $lead_id Lead.
	 * @return object[]
	 */
	public static function notes( $lead_id ) {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE lead_id = %d ORDER BY created_at DESC, id DESC LIMIT 200', Installer::notes_table(), absint( $lead_id ) ) );
	}

	/**
	 * Delete leads older than N days (retention policy).
	 *
	 * @param int $days Days.
	 * @return int
	 */
	public static function purge_older_than( $days ) {
		global $wpdb;
		// DateTime avoids integer overflow of `time() - $days * DAY_IN_SECONDS` on 32-bit PHP.
		$cutoff = ( new \DateTime( 'now', new \DateTimeZone( 'UTC' ) ) )->modify( '-' . min( absint( $days ), 36500 ) . ' days' )->format( 'Y-m-d H:i:s' );
		$ids    = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i WHERE created_at < %s LIMIT 500', Installer::leads_table(), $cutoff ) );
		return $ids ? self::delete( $ids ) : 0;
	}
}
