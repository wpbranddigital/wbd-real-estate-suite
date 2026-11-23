<?php
/**
 * Search/filter query building shared by archives, blocks and REST.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Frontend;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Parses URL filters into WP_Query args, filters the main property archive and
 * keeps filtered URLs out of search indexes.
 */
final class Query {

	/**
	 * Public filter parameter names (GET).
	 */
	const PARAMS = array( 'keyword', 'location', 'type', 'status', 'feature', 'min_price', 'max_price', 'beds', 'baths', 'min_area', 'max_area', 'sort' );

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'pre_get_posts', array( $this, 'filter_main_query' ) );
		add_filter( 'posts_search', array( __CLASS__, 'extend_keyword_search' ), 10, 2 );
		add_filter( 'wp_robots', array( $this, 'robots' ) );
	}

	/**
	 * Read and sanitize filters from an array (defaults to $_GET).
	 *
	 * @param array|null $source Source.
	 * @return array
	 */
	public static function filters_from( $source = null ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only public search filters.
		$source = is_array( $source ) ? $source : wp_unslash( $_GET );
		$f      = array();

		if ( ! empty( $source['keyword'] ) && is_scalar( $source['keyword'] ) ) {
			$f['keyword'] = mb_substr( sanitize_text_field( (string) $source['keyword'] ), 0, 100 );
		}
		foreach ( array( 'location', 'type', 'status', 'feature' ) as $tax_key ) {
			if ( empty( $source[ $tax_key ] ) ) {
				continue;
			}
			$vals = is_array( $source[ $tax_key ] ) ? $source[ $tax_key ] : explode( ',', (string) $source[ $tax_key ] );
			$vals = array_slice( array_values( array_filter( array_map( 'sanitize_title', array_map( 'strval', $vals ) ) ) ), 0, 20 );
			if ( $vals ) {
				$f[ $tax_key ] = $vals;
			}
		}
		foreach ( array( 'min_price', 'max_price', 'min_area', 'max_area' ) as $num_key ) {
			if ( isset( $source[ $num_key ] ) && is_scalar( $source[ $num_key ] ) && '' !== $source[ $num_key ] ) {
				$val = (float) preg_replace( '/[^0-9.]/', '', (string) $source[ $num_key ] );
				if ( $val > 0 ) {
					$f[ $num_key ] = $val;
				}
			}
		}
		foreach ( array( 'beds', 'baths' ) as $int_key ) {
			if ( ! empty( $source[ $int_key ] ) && is_scalar( $source[ $int_key ] ) ) {
				$f[ $int_key ] = min( 20, absint( $source[ $int_key ] ) );
			}
		}
		if ( ! empty( $source['sort'] ) && is_scalar( $source['sort'] ) ) {
			$sort = sanitize_key( (string) $source['sort'] );
			if ( array_key_exists( $sort, self::sort_options() ) ) {
				$f['sort'] = $sort;
			}
		}
		return array_filter( $f );
	}

	/**
	 * Sort options.
	 *
	 * @return array
	 */
	public static function sort_options() {
		return array(
			'newest'     => __( 'Newest', 'wbd-real-estate-suite' ),
			'price_asc'  => __( 'Price: low to high', 'wbd-real-estate-suite' ),
			'price_desc' => __( 'Price: high to low', 'wbd-real-estate-suite' ),
			'area_desc'  => __( 'Largest area', 'wbd-real-estate-suite' ),
			'featured'   => __( 'Featured first', 'wbd-real-estate-suite' ),
			'title'      => __( 'Name (A–Z)', 'wbd-real-estate-suite' ),
		);
	}

	/**
	 * Convert filters to WP_Query arguments.
	 *
	 * @param array $f Sanitized filters.
	 * @return array
	 */
	public static function to_query_args( array $f ) {
		$args = array();
		$tax  = array();
		$meta = array();
		$map  = array(
			'location' => Registrar::LOCATION,
			'type'     => Registrar::TYPE,
			'status'   => Registrar::STATUS,
			'feature'  => Registrar::FEATURE,
		);
		foreach ( $map as $key => $taxonomy ) {
			if ( ! empty( $f[ $key ] ) ) {
				$tax[] = array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $f[ $key ],
					'operator'         => 'feature' === $key ? 'AND' : 'IN',
					'include_children' => true,
				);
			}
		}
		$ranges = array(
			'_wbdres_price' => array( 'min_price', 'max_price' ),
			'_wbdres_area'  => array( 'min_area', 'max_area' ),
		);
		foreach ( $ranges as $meta_key => $keys ) {
			if ( ! empty( $f[ $keys[0] ] ) ) {
				$meta[] = array(
					'key'     => $meta_key,
					'value'   => $f[ $keys[0] ],
					'compare' => '>=',
					'type'    => 'DECIMAL(16,2)',
				);
			}
			if ( ! empty( $f[ $keys[1] ] ) ) {
				$meta[] = array(
					'key'     => $meta_key,
					'value'   => $f[ $keys[1] ],
					'compare' => '<=',
					'type'    => 'DECIMAL(16,2)',
				);
			}
		}
		if ( ! empty( $f['beds'] ) ) {
			$meta[] = array(
				'key'     => '_wbdres_bedrooms',
				'value'   => $f['beds'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( ! empty( $f['baths'] ) ) {
			$meta[] = array(
				'key'     => '_wbdres_bathrooms',
				'value'   => $f['baths'],
				'compare' => '>=',
				'type'    => 'DECIMAL(4,1)',
			);
		}
		if ( $tax ) {
			$tax['relation']   = 'AND';
			$args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		if ( $meta ) {
			$meta['relation']   = 'AND';
			$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}
		if ( ! empty( $f['keyword'] ) ) {
			$args['s']              = $f['keyword'];
			$args['wbdres_keyword'] = $f['keyword'];
		}
		return array_merge( $args, self::sort_args( isset( $f['sort'] ) ? $f['sort'] : 'newest' ) );
	}

	/**
	 * Order arguments for a sort key.
	 *
	 * @param string $sort Sort key.
	 * @return array
	 */
	public static function sort_args( $sort ) {
		switch ( $sort ) {
			case 'price_asc':
			case 'price_desc':
				return array(
					'meta_key' => '_wbdres_price', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'orderby'  => array(
						'meta_value_num' => 'price_asc' === $sort ? 'ASC' : 'DESC',
						'date'           => 'DESC',
					),
				);
			case 'area_desc':
				return array(
					'meta_key' => '_wbdres_area', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'orderby'  => array(
						'meta_value_num' => 'DESC',
						'date'           => 'DESC',
					),
				);
			case 'featured':
				return array(
					'meta_key' => '_wbdres_featured', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'orderby'  => array(
						'meta_value_num' => 'DESC',
						'date'           => 'DESC',
					),
				);
			case 'title':
				return array(
					'orderby' => 'title',
					'order'   => 'ASC',
				);
			default:
				return array(
					'orderby' => 'date',
					'order'   => 'DESC',
				);
		}
	}

	/**
	 * Is this a property archive request?
	 *
	 * @return bool
	 */
	public static function is_property_archive() {
		return is_post_type_archive( Registrar::PROPERTY ) || is_tax( array( Registrar::TYPE, Registrar::STATUS, Registrar::LOCATION, Registrar::FEATURE ) );
	}

	/**
	 * Apply URL filters and page size to the main property archive.
	 *
	 * @param \WP_Query $q Query.
	 */
	public function filter_main_query( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		$is_archive = $q->is_post_type_archive( Registrar::PROPERTY ) || $q->is_tax( array( Registrar::TYPE, Registrar::STATUS, Registrar::LOCATION, Registrar::FEATURE ) );
		if ( ! $is_archive ) {
			return;
		}
		$args = self::to_query_args( self::filters_from() );
		foreach ( $args as $key => $value ) {
			if ( 'tax_query' === $key ) {
				$existing = (array) $q->get( 'tax_query' );
				$value    = array_merge( array_filter( $existing ), $value );
			}
			$q->set( $key, $value );
		}
		$q->set( 'post_type', Registrar::PROPERTY );
		$q->set( 'posts_per_page', (int) Settings::get( 'per_page', 12 ) );
	}

	/**
	 * Extend keyword search to the street address and location names.
	 *
	 * @param string    $search SQL.
	 * @param \WP_Query $q      Query.
	 * @return string
	 */
	public static function extend_keyword_search( $search, $q ) {
		$keyword = $q->get( 'wbdres_keyword' );
		if ( ! $keyword || '' === $search ) {
			return $search;
		}
		global $wpdb;
		$like  = '%' . $wpdb->esc_like( $keyword ) . '%';
		$extra = $wpdb->prepare(
			" OR EXISTS ( SELECT 1 FROM {$wpdb->postmeta} wm WHERE wm.post_id = {$wpdb->posts}.ID AND wm.meta_key = '_wbdres_address' AND wm.meta_value LIKE %s )"
			. " OR EXISTS ( SELECT 1 FROM {$wpdb->term_relationships} wtr INNER JOIN {$wpdb->term_taxonomy} wtt ON wtt.term_taxonomy_id = wtr.term_taxonomy_id INNER JOIN {$wpdb->terms} wt ON wt.term_id = wtt.term_id WHERE wtr.object_id = {$wpdb->posts}.ID AND wtt.taxonomy = %s AND wt.name LIKE %s )",
			$like,
			Registrar::LOCATION,
			$like
		);
		// Keep WordPress' password clause outside our OR group.
		$password = " AND ({$wpdb->posts}.post_password = '')";
		$has_pw   = false !== strpos( $search, $password );
		$search   = rtrim( str_replace( $password, '', $search ) );
		// Wrap WordPress' own " AND (...)" search clause.
		$search = preg_replace( '/^\s*AND\s*\(/', ' AND ( (', $search, 1 );
		return $search . $extra . ' )' . ( $has_pw ? $password : '' ) . ' ';
	}

	/**
	 * Keep filtered/sorted listing URLs out of search indexes (avoid duplicate content).
	 *
	 * @param array $robots Robots directives.
	 * @return array
	 */
	public function robots( $robots ) {
		$has = false;
		foreach ( array_merge( self::PARAMS, array( 'view' ) ) as $param ) {
			if ( isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$has = true;
				break;
			}
		}
		if ( $has ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}
}
