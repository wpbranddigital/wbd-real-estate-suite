<?php
/**
 * Property & agent list-table columns, quick featured toggle, submission rejection.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Support\Format;

defined( 'ABSPATH' ) || exit;

/**
 * Columns.
 */
final class Columns {

	/**
	 * Hooks.
	 */
	public function register() {
		add_filter( 'manage_' . Registrar::PROPERTY . '_posts_columns', array( $this, 'property_columns' ) );
		add_action( 'manage_' . Registrar::PROPERTY . '_posts_custom_column', array( $this, 'property_column' ), 10, 2 );
		add_filter( 'manage_edit-' . Registrar::PROPERTY . '_sortable_columns', array( $this, 'sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_query' ) );
		add_filter( 'manage_' . Registrar::AGENT . '_posts_columns', array( $this, 'agent_columns' ) );
		add_action( 'manage_' . Registrar::AGENT . '_posts_custom_column', array( $this, 'agent_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'admin_post_wbdres_toggle_featured', array( $this, 'toggle_featured' ) );
		add_action( 'admin_post_wbdres_reject', array( $this, 'reject' ) );
		add_action( 'restrict_manage_posts', array( $this, 'filters' ) );
	}

	/**
	 * Columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public function property_columns( $cols ) {
		$new = array();
		foreach ( $cols as $key => $label ) {
			if ( 'title' === $key ) {
				$new['wbdres_thumb'] = '<span class="screen-reader-text">' . esc_html__( 'Photo', 'wbd-real-estate-suite' ) . '</span>';
			}
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['wbdres_price']    = __( 'Price', 'wbd-real-estate-suite' );
				$new['wbdres_specs']    = __( 'Details', 'wbd-real-estate-suite' );
				$new['wbdres_agent']    = __( 'Agent', 'wbd-real-estate-suite' );
				$new['wbdres_featured'] = '<span title="' . esc_attr__( 'Featured', 'wbd-real-estate-suite' ) . '">★<span class="screen-reader-text">' . esc_html__( 'Featured', 'wbd-real-estate-suite' ) . '</span></span>';
				$new['wbdres_views']    = __( 'Views', 'wbd-real-estate-suite' );
			}
		}
		unset( $new['author'] );
		return $new;
	}

	/**
	 * Column output.
	 *
	 * @param string $col     Column.
	 * @param int    $post_id Post.
	 */
	public function property_column( $col, $post_id ) {
		switch ( $col ) {
			case 'wbdres_thumb':
				echo has_post_thumbnail( $post_id ) ? get_the_post_thumbnail(
					$post_id,
					array( 64, 48 ),
					array(
						'class' => 'wbdres-col-thumb',
						'alt'   => '',
					)
				) : '<span class="wbdres-col-thumb wbdres-col-thumb--empty" aria-hidden="true"></span>';
				break;
			case 'wbdres_price':
				echo esc_html( DashboardPage::price( $post_id ) );
				break;
			case 'wbdres_specs':
				$parts = array();
				$beds  = (int) get_post_meta( $post_id, '_wbdres_bedrooms', true );
				$baths = (float) get_post_meta( $post_id, '_wbdres_bathrooms', true );
				$area  = (float) get_post_meta( $post_id, '_wbdres_area', true );
				if ( $beds ) {
					/* translators: %d: bedrooms. */
					$parts[] = sprintf( _n( '%d bed', '%d beds', $beds, 'wbd-real-estate-suite' ), $beds );
				}
				if ( $baths ) {
					/* translators: %s: bathrooms. */
					$parts[] = sprintf( __( '%s baths', 'wbd-real-estate-suite' ), Format::half( $baths ) );
				}
				if ( $area ) {
					$parts[] = Format::area( $area );
				}
				echo $parts ? esc_html( implode( ' · ', $parts ) ) : '—';
				$ref = (string) get_post_meta( $post_id, '_wbdres_reference', true );
				if ( $ref ) {
					echo '<br /><span class="wbdres-muted">' . esc_html( $ref ) . '</span>';
				}
				break;
			case 'wbdres_agent':
				$agent = (int) get_post_meta( $post_id, '_wbdres_agent_id', true );
				echo $agent ? esc_html( get_the_title( $agent ) ) : '—';
				break;
			case 'wbdres_featured':
				$on  = (bool) get_post_meta( $post_id, '_wbdres_featured', true );
				$url = wp_nonce_url( admin_url( 'admin-post.php?action=wbdres_toggle_featured&post=' . $post_id ), 'wbdres_toggle_featured_' . $post_id );
				if ( current_user_can( 'edit_post', $post_id ) ) {
					echo '<a href="' . esc_url( $url ) . '" class="wbdres-star' . ( $on ? ' is-on' : '' ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '"><span aria-hidden="true">' . ( $on ? '★' : '☆' ) . '</span><span class="screen-reader-text">' . esc_html__( 'Toggle featured', 'wbd-real-estate-suite' ) . '</span></a>';
				} else {
					echo $on ? '★' : '';
				}
				break;
			case 'wbdres_views':
				echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, '_wbdres_views', true ) ) );
				break;
		}
	}

	/**
	 * Sortable columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public function sortable( $cols ) {
		$cols['wbdres_price'] = 'wbdres_price';
		$cols['wbdres_views'] = 'wbdres_views';
		return $cols;
	}

	/**
	 * Apply admin sorting and filters.
	 *
	 * @param \WP_Query $q Query.
	 */
	public function sort_query( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || Registrar::PROPERTY !== $q->get( 'post_type' ) ) {
			return;
		}
		$orderby = $q->get( 'orderby' );
		if ( 'wbdres_price' === $orderby || 'wbdres_views' === $orderby ) {
			$q->set( 'meta_key', 'wbdres_price' === $orderby ? '_wbdres_price' : '_wbdres_views' );
			$q->set( 'orderby', 'meta_value_num' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin filters.
		if ( ! empty( $_GET['wbdres_agent'] ) ) {
			$q->set(
				'meta_query',
				array(
					array(
						'key'   => '_wbdres_agent_id',
						'value' => absint( $_GET['wbdres_agent'] ),
					),
				)
			);
		}
		if ( ! empty( $_GET['wbdres_featured'] ) ) {
			$q->set( 'meta_key', '_wbdres_featured' );
			$q->set( 'meta_value', '1' );
		}
		// phpcs:enable
	}

	/**
	 * Extra list filters.
	 *
	 * @param string $post_type Post type.
	 */
	public function filters( $post_type ) {
		if ( Registrar::PROPERTY !== $post_type ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$current = isset( $_GET['wbdres_agent'] ) ? absint( $_GET['wbdres_agent'] ) : 0;
		foreach ( array( Registrar::TYPE, Registrar::STATUS ) as $taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			wp_dropdown_categories(
				array(
					'taxonomy'        => $taxonomy,
					'name'            => $taxonomy,
					'value_field'     => 'slug',
					'selected'        => isset( $_GET[ $taxonomy ] ) ? sanitize_title( wp_unslash( $_GET[ $taxonomy ] ) ) : '',
					/* translators: %s: taxonomy. */
					'show_option_all' => sprintf( __( 'All %s', 'wbd-real-estate-suite' ), $tax->labels->name ),
					'hide_empty'      => false,
					'hierarchical'    => true,
				)
			);
		}
		// phpcs:enable
		$agents = get_posts(
			array(
				'post_type'      => Registrar::AGENT,
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		if ( $agents ) {
			echo '<label class="screen-reader-text" for="wbdres_agent">' . esc_html__( 'Filter by agent', 'wbd-real-estate-suite' ) . '</label><select name="wbdres_agent" id="wbdres_agent"><option value="">' . esc_html__( 'All agents', 'wbd-real-estate-suite' ) . '</option>';
			foreach ( $agents as $a ) {
				echo '<option value="' . esc_attr( $a->ID ) . '"' . selected( $current, $a->ID, false ) . '>' . esc_html( get_the_title( $a ) ) . '</option>';
			}
			echo '</select>';
		}
	}

	/**
	 * Agent columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public function agent_columns( $cols ) {
		$date = $cols['date'] ?? '';
		unset( $cols['date'] );
		$cols['wbdres_contact']  = __( 'Contact', 'wbd-real-estate-suite' );
		$cols['wbdres_listings'] = __( 'Listings', 'wbd-real-estate-suite' );
		$cols['wbdres_user']     = __( 'Linked user', 'wbd-real-estate-suite' );
		if ( $date ) {
			$cols['date'] = $date;
		}
		return $cols;
	}

	/**
	 * Agent column output.
	 *
	 * @param string $col     Column.
	 * @param int    $post_id Post.
	 */
	public function agent_column( $col, $post_id ) {
		switch ( $col ) {
			case 'wbdres_contact':
				$email = (string) get_post_meta( $post_id, '_wbdres_agent_email', true );
				$phone = (string) get_post_meta( $post_id, '_wbdres_agent_phone', true );
				echo esc_html( implode( ' · ', array_filter( array( $email, $phone ) ) ) );
				break;
			case 'wbdres_listings':
				echo esc_html( number_format_i18n( \Wbdres\RealEstate\Frontend\View::agent_property_count( $post_id ) ) );
				break;
			case 'wbdres_user':
				$uid  = (int) get_post_meta( $post_id, '_wbdres_agent_user_id', true );
				$user = $uid ? get_userdata( $uid ) : null;
				echo $user ? esc_html( $user->display_name ) : '—';
				break;
		}
	}

	/**
	 * Add "Reject" for pending frontend submissions.
	 *
	 * @param array    $actions Actions.
	 * @param \WP_Post $post    Post.
	 * @return array
	 */
	public function row_actions( $actions, $post ) {
		if ( Registrar::PROPERTY === $post->post_type && 'pending' === $post->post_status && current_user_can( 'edit_others_wbd_properties' ) && get_post_meta( $post->ID, '_wbdres_submitted', true ) ) {
			$url               = wp_nonce_url( admin_url( 'admin-post.php?action=wbdres_reject&post=' . $post->ID ), 'wbdres_reject_' . $post->ID );
			$actions['reject'] = '<a href="' . esc_url( $url ) . '" class="wbdres-reject" data-prompt="' . esc_attr__( 'Optional: tell the submitter what to change', 'wbd-real-estate-suite' ) . '">' . esc_html__( 'Reject', 'wbd-real-estate-suite' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Toggle featured flag.
	 */
	public function toggle_featured() {
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( 'wbdres_toggle_featured_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) || Registrar::PROPERTY !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'wbd-real-estate-suite' ), 403 );
		}
		update_post_meta( $id, '_wbdres_featured', get_post_meta( $id, '_wbdres_featured', true ) ? 0 : 1 );
		delete_transient( 'wbdres_dashboard_stats' );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=' . Registrar::PROPERTY ) );
		exit;
	}

	/**
	 * Reject a pending submission (moves to draft and notifies submitter).
	 */
	public function reject() {
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( 'wbdres_reject_' . $id );
		if ( ! current_user_can( 'edit_others_wbd_properties' ) || Registrar::PROPERTY !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'wbd-real-estate-suite' ), 403 );
		}
		$reason = isset( $_GET['reason'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_GET['reason'] ) ), 0, 500 ) : '';
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'draft',
			)
		);
		update_post_meta( $id, '_wbdres_reject_reason', $reason );
		do_action( 'wbdres_property_rejected', $id, $reason );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . Registrar::PROPERTY . '&post_status=pending' ) );
		exit;
	}
}
