<?php
/**
 * Property listing grid/list renderer with results toolbar.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Frontend;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;
use Wbdres\RealEstate\Support\Property;

defined( 'ABSPATH' ) || exit;

/**
 * Renders property collections.
 */
final class Listings {

	/**
	 * Default attributes (mirrors the block.json of wbdres/property-listings).
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'perPage'        => 0,
			'columns'        => 3,
			'layout'         => 'grid',
			'cardStyle'      => 'modern',
			'orderBy'        => 'newest',
			'types'          => array(),
			'statuses'       => array(),
			'locations'      => array(),
			'featuredOnly'   => false,
			'agentId'        => 0,
			'similarTo'      => 0,
			'include'        => array(),
			'excludeCurrent' => false,
			'inheritSearch'  => false,
			'useMainQuery'   => false,
			'showPagination' => true,
			'showToolbar'    => false,
			'showViewToggle' => true,
			'showSort'       => true,
			'showChips'      => true,
			'offset'         => 0,
			'showImage'      => true,
			'showPrice'      => true,
			'showAddress'    => true,
			'showSpecs'      => true,
			'showExcerpt'    => false,
			'showAgent'      => false,
			'showBadges'     => true,
			'showActions'    => true,
			'showType'       => false,
			'imageRatio'     => '4/3',
			'headingLevel'   => 3,
			'emptyMessage'   => '',
		);
	}

	/**
	 * Render.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function render( array $atts = array() ) {
		$a = wp_parse_args( $atts, self::defaults() );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public view toggle.
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
		if ( ( $a['inheritSearch'] || $a['useMainQuery'] ) && in_array( $view, array( 'grid', 'list' ), true ) ) {
			$a['layout'] = $view;
		}

		global $wp_query;
		$filters = ( $a['inheritSearch'] || $a['useMainQuery'] ) ? Query::filters_from() : array();

		if ( $a['useMainQuery'] && $wp_query instanceof \WP_Query && Query::is_property_archive() ) {
			$query = $wp_query;
			$paged = max( 1, (int) get_query_var( 'paged' ) );
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public pagination.
			$paged = isset( $_GET['wbd-page'] ) ? max( 1, absint( $_GET['wbd-page'] ) ) : 1;
			$query = self::run_query( self::build_args( $a, $filters, $paged ) );
		}

		Assets::enqueue_frontend();

		$columns = min( 4, max( 1, (int) $a['columns'] ) );
		$layout  = 'list' === $a['layout'] ? 'list' : 'grid';
		$style   = 'list' === $layout ? 'list' : $a['cardStyle'];

		$html = '<div class="wbdres-listings wbdres-listings--' . esc_attr( $layout ) . '">';

		if ( $a['showToolbar'] ) {
			$html .= self::toolbar( $query, $filters, $a, $layout );
		}

		if ( ! $query->have_posts() ) {
			$msg   = $a['emptyMessage'] ? $a['emptyMessage'] : __( 'No properties match your search. Try removing a filter or widening the price range.', 'wbd-real-estate-suite' );
			$html .= '<div class="wbdres-empty" role="status">' . View::icon( 'home' ) . '<p>' . esc_html( $msg ) . '</p>';
			if ( $filters ) {
				$html .= '<a class="wbdres-button wbdres-button--outline" href="' . esc_url( self::base_url() ) . '">' . esc_html__( 'Clear all filters', 'wbd-real-estate-suite' ) . '</a>';
			}
			$html .= '</div></div>';
			wp_reset_postdata();
			return $html;
		}

		$html .= '<ul class="wbdres-grid" role="list" style="--wbdres-cols:' . esc_attr( $columns ) . '">';
		$i     = 0;
		foreach ( $query->posts as $post ) {
			$p = Property::get( $post );
			if ( ! $p ) {
				continue;
			}
			$html .= '<li class="wbdres-grid__item">' . View::card(
				$p,
				array(
					'style'        => $style,
					'image'        => (bool) $a['showImage'],
					'price'        => (bool) $a['showPrice'],
					'address'      => (bool) $a['showAddress'],
					'specs'        => (bool) $a['showSpecs'],
					'excerpt'      => (bool) $a['showExcerpt'],
					'agent'        => (bool) $a['showAgent'],
					'badges'       => (bool) $a['showBadges'],
					'actions'      => (bool) $a['showActions'],
					'type'         => (bool) $a['showType'],
					'ratio'        => $a['imageRatio'],
					'headingLevel' => (int) $a['headingLevel'],
					'eager'        => 0 === $i && $a['useMainQuery'],
				)
			) . '</li>';
			++$i;
		}
		$html .= '</ul>';

		if ( $a['showPagination'] && $query->max_num_pages > 1 ) {
			$html .= self::pagination( $query, $paged, (bool) $a['useMainQuery'] );
		}
		$html .= '</div>';

		if ( $query !== $wp_query ) {
			wp_reset_postdata();
		}
		return $html;
	}

	/**
	 * Build WP_Query args.
	 *
	 * @param array $a       Attributes.
	 * @param array $filters URL filters.
	 * @param int   $paged   Page.
	 * @return array
	 */
	public static function build_args( array $a, array $filters, $paged ) {
		$per_page = (int) $a['perPage'] > 0 ? min( 100, (int) $a['perPage'] ) : (int) Settings::get( 'per_page', 12 );
		$args     = array(
			'post_type'           => Registrar::PROPERTY,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => $paged,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => ! $a['showPagination'] && ! $a['showToolbar'],
		);
		if ( (int) $a['offset'] > 0 && 1 === $paged ) {
			$args['offset'] = (int) $a['offset'];
		}

		$tax = array();
		foreach ( array(
			'types'     => Registrar::TYPE,
			'statuses'  => Registrar::STATUS,
			'locations' => Registrar::LOCATION,
		) as $key => $taxonomy ) {
			$ids = array_filter( array_map( 'absint', (array) $a[ $key ] ) );
			if ( $ids ) {
				$tax[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $ids,
				);
			}
		}
		$meta = array();
		if ( $a['featuredOnly'] ) {
			$meta[] = array(
				'key'   => '_wbdres_featured',
				'value' => '1',
			);
		}
		$agent_id = (int) $a['agentId'];
		if ( -1 === $agent_id ) {
			$current  = get_post();
			$agent_id = ( $current && Registrar::AGENT === $current->post_type ) ? $current->ID : 0;
			if ( ! $agent_id ) {
				$args['post__in'] = array( 0 );
			}
		}
		if ( $agent_id > 0 ) {
			$meta[] = array(
				'key'   => '_wbdres_agent_id',
				'value' => $agent_id,
				'type'  => 'NUMERIC',
			);
		}

		$similar = (int) $a['similarTo'];
		if ( -1 === $similar ) {
			$current = get_post();
			$similar = ( $current && Registrar::PROPERTY === $current->post_type ) ? $current->ID : 0;
		}
		// IDs to leave out (current / reference property). Removed in PHP by run_query(),
		// which avoids the slow `post__not_in` SQL clause.
		$exclude = array();
		if ( $similar > 0 ) {
			$exclude[] = $similar;
			$or        = array( 'relation' => 'OR' );
			foreach ( array( Registrar::TYPE, Registrar::LOCATION ) as $taxonomy ) {
				$terms = wp_get_post_terms( $similar, $taxonomy, array( 'fields' => 'ids' ) );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$or[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $terms,
					);
				}
			}
			if ( count( $or ) > 1 ) {
				$tax[] = $or;
			}
		}

		if ( $a['excludeCurrent'] && is_singular( Registrar::PROPERTY ) ) {
			$exclude[] = get_queried_object_id();
		}
		$exclude = array_values( array_unique( array_filter( array_map( 'absint', $exclude ) ) ) );
		if ( $exclude ) {
			$args['wbdres_exclude'] = $exclude;
		}
		$include = array_filter( array_map( 'absint', (array) $a['include'] ) );
		if ( $include ) {
			$args['post__in'] = $include;
			$args['orderby']  = 'post__in';
		}

		$filter_args = $filters ? Query::to_query_args( $filters ) : Query::sort_args( $a['orderBy'] );
		if ( ! $filters || empty( $filters['sort'] ) ) {
			$filter_args = array_merge( $filter_args, Query::sort_args( $a['orderBy'] ) );
		}
		if ( ! empty( $filter_args['tax_query'] ) ) {
			unset( $filter_args['tax_query']['relation'] );
			$tax = array_merge( $tax, $filter_args['tax_query'] );
			unset( $filter_args['tax_query'] );
		}
		if ( ! empty( $filter_args['meta_query'] ) ) {
			unset( $filter_args['meta_query']['relation'] );
			$meta = array_merge( $meta, $filter_args['meta_query'] );
			unset( $filter_args['meta_query'] );
		}
		if ( 'rand' === $a['orderBy'] && ! $filters ) {
			$filter_args['orderby'] = 'rand';
		}
		$args = array_merge( $args, $filter_args );
		if ( $include ) {
			$args['orderby'] = 'post__in';
			unset( $args['meta_key'] );
		}
		if ( $tax ) {
			$tax['relation']   = 'AND';
			$args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		if ( $meta ) {
			$meta['relation']   = 'AND';
			$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		/**
		 * Filter the property listing query arguments.
		 *
		 * @param array $args WP_Query args.
		 * @param array $a    Block attributes.
		 */
		return apply_filters( 'wbdres_listing_query_args', $args, $a );
	}

	/**
	 * Run the listing query. When `wbdres_exclude` IDs are present they are removed in PHP
	 * instead of SQL `post__not_in`: fetch the matching IDs up to the current page (cheap,
	 * IDs only), drop the excluded ones, slice the page and load just those posts.
	 * found_posts and max_num_pages stay exact so pagination and the result count work.
	 *
	 * @param array $args WP_Query args from build_args().
	 * @return \WP_Query
	 */
	public static function run_query( array $args ) {
		$exclude = isset( $args['wbdres_exclude'] ) ? array_map( 'absint', (array) $args['wbdres_exclude'] ) : array();
		unset( $args['wbdres_exclude'] );
		if ( ! $exclude ) {
			return new \WP_Query( $args );
		}

		$per_page = max( 1, (int) $args['posts_per_page'] );
		$paged    = max( 1, (int) ( $args['paged'] ?? 1 ) );
		$offset   = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;
		$counted  = empty( $args['no_found_rows'] );

		// 1) Matching IDs up to the end of this page, plus room for the excluded ones.
		$id_args = array_merge(
			$args,
			array(
				'fields'                 => 'ids',
				'posts_per_page'         => $offset + $paged * $per_page + count( $exclude ),
				'paged'                  => 1,
				'no_found_rows'          => ! $counted,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		unset( $id_args['offset'] );
		$id_query = new \WP_Query( $id_args );
		$ids      = array_values( array_diff( array_map( 'intval', $id_query->posts ), $exclude ) );

		// 2) Exact total: every match minus the excluded IDs that also match the filters.
		$total = count( $ids );
		if ( $counted ) {
			// Respect an existing post__in restriction (e.g. the "include" attribute).
			$candidates = isset( $args['post__in'] ) ? array_values( array_intersect( $exclude, array_map( 'intval', (array) $args['post__in'] ) ) ) : $exclude;
			$hidden     = 0;
			if ( $candidates ) {
				$hidden_query = new \WP_Query(
					array_merge(
						$id_args,
						array(
							'post__in'       => $candidates,
							'posts_per_page' => count( $candidates ),
							'no_found_rows'  => true,
						)
					)
				);
				$hidden       = count( $hidden_query->posts );
			}
			$total = max( 0, (int) $id_query->found_posts - $hidden );
		}

		// 3) Load only the posts for this page, in the same order.
		$page_ids = array_slice( $ids, $offset + ( $paged - 1 ) * $per_page, $per_page );
		$final    = array_merge(
			$args,
			array(
				'post__in'       => $page_ids ? $page_ids : array( 0 ),
				'orderby'        => 'post__in',
				'posts_per_page' => $per_page,
				'paged'          => 1,
				'no_found_rows'  => true,
			)
		);
		unset( $final['offset'], $final['order'] );
		$query                = new \WP_Query( $final );
		$query->found_posts   = $total;
		$query->max_num_pages = (int) ceil( max( 0, $total - $offset ) / $per_page );
		return $query;
	}

	/**
	 * Current page URL without pagination.
	 *
	 * @return string
	 */
	public static function base_url() {
		if ( Query::is_property_archive() ) {
			$obj = get_queried_object();
			return $obj instanceof \WP_Term ? get_term_link( $obj ) : get_post_type_archive_link( Registrar::PROPERTY );
		}
		$id = get_queried_object_id();
		return $id ? get_permalink( $id ) : home_url( '/' );
	}

	/**
	 * Results toolbar: count, chips, sort, view toggle.
	 *
	 * @param \WP_Query $query   Query.
	 * @param array     $filters Filters.
	 * @param array     $a       Atts.
	 * @param string    $layout  Layout.
	 * @return string
	 */
	public static function toolbar( $query, array $filters, array $a, $layout ) {
		$base    = self::base_url();
		$current = array_filter( array_intersect_key( $filters, array_flip( Query::PARAMS ) ) );
		$found   = (int) $query->found_posts;

		$html  = '<div class="wbdres-toolbar">';
		$html .= '<p class="wbdres-toolbar__count" role="status">' . sprintf(
			/* translators: %s: number of properties. */
			esc_html( _n( '%s property found', '%s properties found', $found, 'wbd-real-estate-suite' ) ),
			'<strong>' . esc_html( number_format_i18n( $found ) ) . '</strong>'
		) . '</p>';

		$html .= '<div class="wbdres-toolbar__controls">';
		if ( $a['showSort'] ) {
			$sort  = isset( $filters['sort'] ) ? $filters['sort'] : $a['orderBy'];
			$html .= '<form method="get" action="' . esc_url( $base ) . '" class="wbdres-sort" data-wp-interactive="wbdres">';
			foreach ( $current as $key => $value ) {
				if ( 'sort' === $key ) {
					continue;
				}
				foreach ( (array) $value as $v ) {
					$html .= '<input type="hidden" name="' . esc_attr( is_array( $value ) ? $key . '[]' : $key ) . '" value="' . esc_attr( $v ) . '" />';
				}
			}
			if ( 'list' === $layout ) {
				$html .= '<input type="hidden" name="view" value="list" />';
			}
			$html .= '<label for="wbdres-sort-select">' . esc_html__( 'Sort by', 'wbd-real-estate-suite' ) . '</label><select id="wbdres-sort-select" name="sort" data-wp-on--change="actions.submitParent">';
			foreach ( Query::sort_options() as $key => $label ) {
				$html .= '<option value="' . esc_attr( $key ) . '"' . selected( $sort, $key, false ) . '>' . esc_html( $label ) . '</option>';
			}
			$html .= '</select><noscript><button type="submit" class="wbdres-button wbdres-button--small">' . esc_html__( 'Apply', 'wbd-real-estate-suite' ) . '</button></noscript></form>';
		}
		if ( $a['showViewToggle'] ) {
			$params = self::flatten( $current );
			$html  .= '<div class="wbdres-view-toggle" role="group" aria-label="' . esc_attr__( 'Layout', 'wbd-real-estate-suite' ) . '">';
			foreach ( array(
				'grid' => __( 'Grid', 'wbd-real-estate-suite' ),
				'list' => __( 'List', 'wbd-real-estate-suite' ),
			) as $key => $label ) {
				$url   = add_query_arg( array_merge( $params, array( 'view' => $key ) ), $base );
				$html .= '<a href="' . esc_url( $url ) . '" class="wbdres-view-toggle__btn' . ( $key === $layout ? ' is-active' : '' ) . '"' . ( $key === $layout ? ' aria-current="true"' : '' ) . ' rel="nofollow">' . self::layout_icon( $key ) . '<span>' . esc_html( $label ) . '</span></a>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';

		if ( $a['showChips'] ) {
			$chips = self::chips( $current, $base );
			if ( $chips ) {
				$html .= '<div class="wbdres-chips" aria-label="' . esc_attr__( 'Active filters', 'wbd-real-estate-suite' ) . '">' . $chips . '<a class="wbdres-chips__clear" href="' . esc_url( $base ) . '">' . esc_html__( 'Clear all', 'wbd-real-estate-suite' ) . '</a></div>';
			}
		}
		return $html . '</div>';
	}

	/**
	 * Active filter chips.
	 *
	 * @param array  $current Filters.
	 * @param string $base    Base URL.
	 * @return string
	 */
	private static function chips( array $current, $base ) {
		$html     = '';
		$tax_map  = array(
			'location' => Registrar::LOCATION,
			'type'     => Registrar::TYPE,
			'status'   => Registrar::STATUS,
			'feature'  => Registrar::FEATURE,
		);
		$labelize = function ( $key, $value ) use ( $tax_map ) {
			if ( isset( $tax_map[ $key ] ) ) {
				$term = get_term_by( 'slug', $value, $tax_map[ $key ] );
				return $term ? $term->name : $value;
			}
			switch ( $key ) {
				case 'keyword':
					return '“' . $value . '”';
				case 'min_price':
					/* translators: %s: price. */
					return sprintf( __( 'From %s', 'wbd-real-estate-suite' ), \Wbdres\RealEstate\Support\Format::price( $value ) );
				case 'max_price':
					/* translators: %s: price. */
					return sprintf( __( 'Up to %s', 'wbd-real-estate-suite' ), \Wbdres\RealEstate\Support\Format::price( $value ) );
				case 'beds':
					/* translators: %d: bedrooms. */
					return sprintf( __( '%d+ beds', 'wbd-real-estate-suite' ), $value );
				case 'baths':
					/* translators: %d: bathrooms. */
					return sprintf( __( '%d+ baths', 'wbd-real-estate-suite' ), $value );
				case 'min_area':
					/* translators: %s: area. */
					return sprintf( __( 'Min %s', 'wbd-real-estate-suite' ), \Wbdres\RealEstate\Support\Format::area( $value ) );
				case 'max_area':
					/* translators: %s: area. */
					return sprintf( __( 'Max %s', 'wbd-real-estate-suite' ), \Wbdres\RealEstate\Support\Format::area( $value ) );
			}
			return '';
		};
		foreach ( $current as $key => $value ) {
			if ( 'sort' === $key ) {
				continue;
			}
			foreach ( (array) $value as $v ) {
				$label = $labelize( $key, $v );
				if ( '' === $label ) {
					continue;
				}
				$remaining = $current;
				if ( is_array( $value ) ) {
					$remaining[ $key ] = array_values( array_diff( $value, array( $v ) ) );
					if ( ! $remaining[ $key ] ) {
						unset( $remaining[ $key ] );
					}
				} else {
					unset( $remaining[ $key ] );
				}
				$url = add_query_arg( self::flatten( $remaining ), $base );
				/* translators: %s: filter label. */
				$html .= '<a class="wbdres-chip wbdres-chip--removable" href="' . esc_url( $url ) . '" rel="nofollow"><span>' . esc_html( $label ) . '</span>' . View::icon( 'close' ) . '<span class="screen-reader-text">' . esc_html( sprintf( __( 'Remove filter: %s', 'wbd-real-estate-suite' ), $label ) ) . '</span></a>';
			}
		}
		return $html;
	}

	/**
	 * Flatten filters to query args (arrays become comma lists).
	 *
	 * @param array $filters Filters.
	 * @return array
	 */
	public static function flatten( array $filters ) {
		$out = array();
		foreach ( $filters as $k => $v ) {
			$out[ $k ] = is_array( $v ) ? implode( ',', $v ) : $v;
		}
		return array_map( 'rawurlencode', $out );
	}

	/**
	 * Grid/list icon.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	private static function layout_icon( $key ) {
		$path = 'grid' === $key ? '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>' : '<rect x="4" y="5" width="6" height="5" rx="1"/><rect x="4" y="14" width="6" height="5" rx="1"/><path d="M13 7h7M13 16h7"/>';
		return '<svg class="wbdres-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false">' . $path . '</svg>';
	}

	/**
	 * Accessible pagination.
	 *
	 * @param \WP_Query $query Query.
	 * @param int       $paged Current page.
	 * @param bool      $main  Main query pagination.
	 * @return string
	 */
	public static function pagination( $query, $paged, $main ) {
		$args = array(
			'total'     => (int) $query->max_num_pages,
			'current'   => $paged,
			'type'      => 'list',
			'prev_text' => View::icon( 'prev' ) . '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'wbd-real-estate-suite' ) . '</span>',
			'next_text' => View::icon( 'next' ) . '<span class="screen-reader-text">' . esc_html__( 'Next page', 'wbd-real-estate-suite' ) . '</span>',
			'mid_size'  => 1,
		);
		if ( ! $main ) {
			$args['base']   = esc_url_raw( add_query_arg( 'wbd-page', '%#%' ) );
			$args['format'] = '';
		}
		$links = paginate_links( $args );
		return $links ? '<nav class="wbdres-pagination" aria-label="' . esc_attr__( 'Property results pages', 'wbd-real-estate-suite' ) . '">' . $links . '</nav>' : '';
	}
}
