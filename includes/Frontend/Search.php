<?php
/**
 * Property search form renderer (bar, sidebar and compact layouts). Works without JavaScript.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Frontend;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Search form.
 */
final class Search {

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'layout'       => 'bar',
			'resultsPage'  => 0,
			'statusTabs'   => true,
			'showKeyword'  => true,
			'showLocation' => true,
			'showType'     => true,
			'showStatus'   => false,
			'showPrice'    => true,
			'showRooms'    => true,
			'showArea'     => true,
			'showFeatures' => true,
			'buttonText'   => '',
			'title'        => '',
		);
	}

	/**
	 * Render.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function render( array $atts = array() ) {
		static $n = 0;
		++$n;
		$a      = wp_parse_args( $atts, self::defaults() );
		$layout = in_array( $a['layout'], array( 'bar', 'sidebar', 'compact' ), true ) ? $a['layout'] : 'bar';
		$uid    = 'wbdres-s' . $n;
		$f      = Query::filters_from();
		$action = $a['resultsPage'] ? get_permalink( (int) $a['resultsPage'] ) : '';
		if ( ! $action ) {
			$action = Query::is_property_archive() ? Listings::base_url() : get_post_type_archive_link( Registrar::PROPERTY );
		}
		$button = $a['buttonText'] ? $a['buttonText'] : ( 'sidebar' === $layout ? __( 'Show results', 'wbd-real-estate-suite' ) : __( 'Search', 'wbd-real-estate-suite' ) );
		Assets::enqueue_frontend();

		$html = '<form role="search" method="get" action="' . esc_url( $action ) . '" class="wbdres-search wbdres-search--' . esc_attr( $layout ) . '" aria-label="' . esc_attr__( 'Property search', 'wbd-real-estate-suite' ) . '">';
		if ( $a['title'] ) {
			$html .= '<h2 class="wbdres-search__title">' . esc_html( $a['title'] ) . '</h2>';
		}

		if ( $a['statusTabs'] ) {
			$html .= self::status_tabs( $uid, $f );
		}

		if ( 'sidebar' === $layout ) {
			$html .= self::sidebar_fields( $uid, $a, $f );
		} else {
			$html .= '<div class="wbdres-search__row">';
			if ( $a['showKeyword'] ) {
				$html .= '<div class="wbdres-search__field wbdres-search__field--keyword"><label for="' . esc_attr( $uid ) . '-kw" class="screen-reader-text">' . esc_html__( 'City, address or keyword', 'wbd-real-estate-suite' ) . '</label>' . View::icon( 'pin' ) . '<input type="search" id="' . esc_attr( $uid ) . '-kw" name="keyword" value="' . esc_attr( isset( $f['keyword'] ) ? $f['keyword'] : '' ) . '" placeholder="' . esc_attr__( 'City, address or keyword', 'wbd-real-estate-suite' ) . '" autocomplete="off" /></div>';
			}
			if ( $a['showLocation'] ) {
				$html .= self::term_select( $uid, 'location', Registrar::LOCATION, __( 'Location', 'wbd-real-estate-suite' ), __( 'Any location', 'wbd-real-estate-suite' ), $f );
			}
			if ( $a['showType'] ) {
				$html .= self::term_select( $uid, 'type', Registrar::TYPE, __( 'Property type', 'wbd-real-estate-suite' ), __( 'Any type', 'wbd-real-estate-suite' ), $f );
			}
			if ( $a['showStatus'] && ! $a['statusTabs'] ) {
				$html .= self::term_select( $uid, 'status', Registrar::STATUS, __( 'Status', 'wbd-real-estate-suite' ), __( 'Any status', 'wbd-real-estate-suite' ), $f );
			}
			if ( 'bar' === $layout && $a['showPrice'] ) {
				$html .= '<details class="wbdres-dropdown"><summary class="wbdres-search__field">' . View::icon( 'tag' ) . '<span>' . esc_html__( 'Price range', 'wbd-real-estate-suite' ) . '</span></summary><div class="wbdres-dropdown__panel">' . self::range( $uid, 'price', __( 'Min price', 'wbd-real-estate-suite' ), __( 'Max price', 'wbd-real-estate-suite' ), $f ) . '</div></details>';
			}
			if ( 'bar' === $layout && $a['showRooms'] ) {
				$html .= '<details class="wbdres-dropdown"><summary class="wbdres-search__field">' . View::icon( 'bed' ) . '<span>' . esc_html__( 'Beds & baths', 'wbd-real-estate-suite' ) . '</span></summary><div class="wbdres-dropdown__panel">' . self::rooms( $uid, $f ) . '</div></details>';
			}
			$html .= '<button type="submit" class="wbdres-button wbdres-search__submit">' . esc_html( $button ) . '</button>';
			$html .= '</div>';
		}
		return $html . '</form>';
	}

	/**
	 * Segmented status control (Buy / Rent …) as radio buttons.
	 *
	 * @param string $uid UID.
	 * @param array  $f   Filters.
	 * @return string
	 */
	private static function status_tabs( $uid, array $f ) {
		$terms = get_terms(
			array(
				'taxonomy'   => Registrar::STATUS,
				'hide_empty' => false,
				'number'     => 6,
				'parent'     => 0,
			)
		);
		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}
		$current = isset( $f['status'][0] ) ? $f['status'][0] : '';
		$html    = '<fieldset class="wbdres-segmented"><legend class="screen-reader-text">' . esc_html__( 'Listing status', 'wbd-real-estate-suite' ) . '</legend>';
		$html   .= '<input type="radio" id="' . esc_attr( $uid ) . '-st-all" name="status" value=""' . checked( '', $current, false ) . ' /><label for="' . esc_attr( $uid ) . '-st-all">' . esc_html__( 'All', 'wbd-real-estate-suite' ) . '</label>';
		foreach ( $terms as $t ) {
			$id    = $uid . '-st-' . $t->term_id;
			$html .= '<input type="radio" id="' . esc_attr( $id ) . '" name="status" value="' . esc_attr( $t->slug ) . '"' . checked( $t->slug, $current, false ) . ' /><label for="' . esc_attr( $id ) . '">' . esc_html( $t->name ) . '</label>';
		}
		return $html . '</fieldset>';
	}

	/**
	 * Taxonomy select.
	 *
	 * @param string $uid      UID.
	 * @param string $name     Param.
	 * @param string $taxonomy Taxonomy.
	 * @param string $label    Label.
	 * @param string $any      "Any" option.
	 * @param array  $f        Filters.
	 * @return string
	 */
	private static function term_select( $uid, $name, $taxonomy, $label, $any, array $f ) {
		$terms = self::terms( $taxonomy );
		if ( ! $terms ) {
			return '';
		}
		$current = isset( $f[ $name ][0] ) ? $f[ $name ][0] : '';
		$html    = '<div class="wbdres-search__field wbdres-search__field--select"><label for="' . esc_attr( $uid . '-' . $name ) . '" class="screen-reader-text">' . esc_html( $label ) . '</label>' . View::icon( 'location' === $name ? 'globe' : 'home' ) . '<select id="' . esc_attr( $uid . '-' . $name ) . '" name="' . esc_attr( $name ) . '"><option value="">' . esc_html( $any ) . '</option>';
		foreach ( $terms as $t ) {
			$indent = str_repeat( '— ', (int) $t->depth );
			$html  .= '<option value="' . esc_attr( $t->slug ) . '"' . selected( $current, $t->slug, false ) . '>' . esc_html( $indent . $t->name ) . '</option>';
		}
		return $html . '</select></div>';
	}

	/**
	 * Terms in hierarchical order with depth (cached per request).
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return array
	 */
	public static function terms( $taxonomy ) {
		static $cache = array();
		if ( isset( $cache[ $taxonomy ] ) ) {
			return $cache[ $taxonomy ];
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 300,
				'orderby'    => 'name',
			)
		);
		if ( ! $terms || is_wp_error( $terms ) ) {
			$cache[ $taxonomy ] = array();
			return array();
		}
		$by_parent = array();
		foreach ( $terms as $t ) {
			$by_parent[ (int) $t->parent ][] = $t;
		}
		$out  = array();
		$walk = function ( $parent_id, $depth ) use ( &$walk, &$out, $by_parent ) {
			if ( empty( $by_parent[ $parent_id ] ) || $depth > 4 ) {
				return;
			}
			foreach ( $by_parent[ $parent_id ] as $t ) {
				$t->depth = $depth;
				$out[]    = $t;
				$walk( (int) $t->term_id, $depth + 1 );
			}
		};
		$walk( 0, 0 );
		$cache[ $taxonomy ] = $out;
		return $out;
	}

	/**
	 * Min/max number inputs.
	 *
	 * @param string $uid       UID.
	 * @param string $key       price|area.
	 * @param string $min_label Min label.
	 * @param string $max_label Max label.
	 * @param array  $f         Filters.
	 * @return string
	 */
	private static function range( $uid, $key, $min_label, $max_label, array $f ) {
		$min = isset( $f[ 'min_' . $key ] ) ? $f[ 'min_' . $key ] : '';
		$max = isset( $f[ 'max_' . $key ] ) ? $f[ 'max_' . $key ] : '';
		$sym = 'price' === $key ? (string) Settings::get( 'currency_symbol', '$' ) : '';
		return '<div class="wbdres-range">'
			. '<div class="wbdres-field"><label for="' . esc_attr( "{$uid}-min-{$key}" ) . '">' . esc_html( $min_label ) . '</label><input type="number" inputmode="numeric" min="0" step="any" id="' . esc_attr( "{$uid}-min-{$key}" ) . '" name="' . esc_attr( 'min_' . $key ) . '" value="' . esc_attr( $min ) . '" placeholder="' . esc_attr( $sym . '0' ) . '" /></div>'
			. '<div class="wbdres-field"><label for="' . esc_attr( "{$uid}-max-{$key}" ) . '">' . esc_html( $max_label ) . '</label><input type="number" inputmode="numeric" min="0" step="any" id="' . esc_attr( "{$uid}-max-{$key}" ) . '" name="' . esc_attr( 'max_' . $key ) . '" value="' . esc_attr( $max ) . '" placeholder="' . esc_attr__( 'Any', 'wbd-real-estate-suite' ) . '" /></div>'
			. '</div>';
	}

	/**
	 * Beds and baths "N+" pill radios.
	 *
	 * @param string $uid UID.
	 * @param array  $f   Filters.
	 * @return string
	 */
	private static function rooms( $uid, array $f ) {
		$html = '';
		foreach ( array(
			'beds'  => __( 'Bedrooms', 'wbd-real-estate-suite' ),
			'baths' => __( 'Bathrooms', 'wbd-real-estate-suite' ),
		) as $key => $label ) {
			$current = isset( $f[ $key ] ) ? (int) $f[ $key ] : 0;
			$html   .= '<fieldset class="wbdres-pills"><legend>' . esc_html( $label ) . '</legend>';
			foreach ( array( 0, 1, 2, 3, 4, 5 ) as $n ) {
				$id    = "{$uid}-{$key}-{$n}";
				$html .= '<input type="radio" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $n ? $n : '' ) . '"' . checked( $current, $n, false ) . ' /><label for="' . esc_attr( $id ) . '">' . esc_html( $n ? $n . '+' : __( 'Any', 'wbd-real-estate-suite' ) ) . '</label>';
			}
			$html .= '</fieldset>';
		}
		return $html;
	}

	/**
	 * Sidebar filter panel.
	 *
	 * @param string $uid UID.
	 * @param array  $a   Atts.
	 * @param array  $f   Filters.
	 * @return string
	 */
	private static function sidebar_fields( $uid, array $a, array $f ) {
		$html = '<div class="wbdres-filters"><p class="wbdres-filters__title">' . esc_html__( 'Filters', 'wbd-real-estate-suite' ) . '</p>';
		if ( $a['showKeyword'] ) {
			$html .= '<div class="wbdres-search__field wbdres-search__field--keyword"><label for="' . esc_attr( $uid ) . '-kw" class="screen-reader-text">' . esc_html__( 'City, address or keyword', 'wbd-real-estate-suite' ) . '</label>' . View::icon( 'pin' ) . '<input type="search" id="' . esc_attr( $uid ) . '-kw" name="keyword" value="' . esc_attr( isset( $f['keyword'] ) ? $f['keyword'] : '' ) . '" placeholder="' . esc_attr__( 'City, address or keyword', 'wbd-real-estate-suite' ) . '" /></div>';
		}
		$groups = array();
		if ( $a['showType'] ) {
			$groups[] = array( 'type', Registrar::TYPE, __( 'Property type', 'wbd-real-estate-suite' ), 'home', 'checks' );
		}
		if ( $a['showLocation'] ) {
			$groups[] = array( 'location', Registrar::LOCATION, __( 'Location', 'wbd-real-estate-suite' ), 'pin', 'checks' );
		}
		if ( $a['showStatus'] && ! $a['statusTabs'] ) {
			$groups[] = array( 'status', Registrar::STATUS, __( 'Status', 'wbd-real-estate-suite' ), 'tag', 'checks' );
		}
		foreach ( $groups as $g ) {
			$terms = self::terms( $g[1] );
			if ( ! $terms ) {
				continue;
			}
			$selected = isset( $f[ $g[0] ] ) ? $f[ $g[0] ] : array();
			$html    .= '<details class="wbdres-filter-group" open><summary>' . View::icon( $g[3] ) . '<span>' . esc_html( $g[2] ) . '</span></summary><fieldset><legend class="screen-reader-text">' . esc_html( $g[2] ) . '</legend><ul class="wbdres-checks">';
			foreach ( $terms as $t ) {
				$id    = $uid . '-' . $g[0] . '-' . $t->term_id;
				$html .= '<li style="--depth:' . esc_attr( (int) $t->depth ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $g[0] ) . '[]" value="' . esc_attr( $t->slug ) . '"' . checked( in_array( $t->slug, $selected, true ), true, false ) . ' /><label for="' . esc_attr( $id ) . '">' . esc_html( $t->name ) . '</label></li>';
			}
			$html .= '</ul></fieldset></details>';
		}
		if ( $a['showPrice'] ) {
			$html .= '<details class="wbdres-filter-group" open><summary>' . View::icon( 'tag' ) . '<span>' . esc_html__( 'Price range', 'wbd-real-estate-suite' ) . '</span></summary>' . self::range( $uid, 'price', __( 'Minimum', 'wbd-real-estate-suite' ), __( 'Maximum', 'wbd-real-estate-suite' ), $f ) . '</details>';
		}
		if ( $a['showRooms'] ) {
			$html .= '<details class="wbdres-filter-group" open><summary>' . View::icon( 'bed' ) . '<span>' . esc_html__( 'Rooms', 'wbd-real-estate-suite' ) . '</span></summary>' . self::rooms( $uid, $f ) . '</details>';
		}
		if ( $a['showArea'] ) {
			$units = Settings::area_units();
			$unit  = Settings::get( 'area_unit', 'sqft' );
			/* translators: %s: area unit. */
			$html .= '<details class="wbdres-filter-group"><summary>' . View::icon( 'area' ) . '<span>' . esc_html( sprintf( __( 'Floor area (%s)', 'wbd-real-estate-suite' ), isset( $units[ $unit ] ) ? $units[ $unit ] : $unit ) ) . '</span></summary>' . self::range( $uid, 'area', __( 'Minimum', 'wbd-real-estate-suite' ), __( 'Maximum', 'wbd-real-estate-suite' ), $f ) . '</details>';
		}
		if ( $a['showFeatures'] ) {
			$terms = self::terms( Registrar::FEATURE );
			if ( $terms ) {
				$selected = isset( $f['feature'] ) ? $f['feature'] : array();
				$html    .= '<details class="wbdres-filter-group" open><summary>' . View::icon( 'check' ) . '<span>' . esc_html__( 'Amenities', 'wbd-real-estate-suite' ) . '</span></summary><fieldset><legend class="screen-reader-text">' . esc_html__( 'Amenities', 'wbd-real-estate-suite' ) . '</legend><div class="wbdres-chip-checks">';
				foreach ( $terms as $t ) {
					$id    = $uid . '-feat-' . $t->term_id;
					$html .= '<input type="checkbox" id="' . esc_attr( $id ) . '" name="feature[]" value="' . esc_attr( $t->slug ) . '"' . checked( in_array( $t->slug, $selected, true ), true, false ) . ' /><label for="' . esc_attr( $id ) . '" class="wbdres-chip">' . esc_html( $t->name ) . '</label>';
				}
				$html .= '</div></fieldset></details>';
			}
		}
		$button = $a['buttonText'] ? $a['buttonText'] : __( 'Show results', 'wbd-real-estate-suite' );
		$html  .= '<button type="submit" class="wbdres-button wbdres-button--block">' . esc_html( $button ) . '</button>';
		return $html . '</div>';
	}
}
