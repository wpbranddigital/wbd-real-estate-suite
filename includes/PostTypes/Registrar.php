<?php
/**
 * Custom post types and taxonomies.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\PostTypes;

use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `wbd_property`, `wbd_agent` and the four property taxonomies.
 */
final class Registrar {

	const PROPERTY = 'wbd_property';
	const AGENT    = 'wbd_agent';
	const TYPE     = 'wbd_property_type';
	const STATUS   = 'wbd_property_status';
	const LOCATION = 'wbd_property_location';
	const FEATURE  = 'wbd_property_feature';

	/**
	 * Hook registration.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_types' ), 5 );
		add_action( 'init', array( $this, 'register_taxonomies' ), 5 );
		add_action( 'init', array( $this, 'maybe_flush' ), 99 );
		add_action( 'save_post_' . self::PROPERTY, array( __CLASS__, 'ensure_meta' ), 20 );
		add_action( 'save_post_' . self::PROPERTY, array( __CLASS__, 'flush_counts' ), 30 );
	}

	/**
	 * Make sure sortable numeric keys exist so "sort by price/area/featured"
	 * never drops properties that lack a value.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function ensure_meta( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		foreach ( array( '_wbdres_price', '_wbdres_area', '_wbdres_bedrooms', '_wbdres_bathrooms', '_wbdres_featured' ) as $key ) {
			if ( ! metadata_exists( 'post', $post_id, $key ) ) {
				add_post_meta( $post_id, $key, 0, true );
			}
		}
	}

	/**
	 * Clear cached counters after a property changes.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function flush_counts( $post_id ) {
		$agent = (int) get_post_meta( $post_id, '_wbdres_agent_id', true );
		if ( $agent ) {
			wp_cache_delete( 'agent_count_' . $agent, 'wbdres' );
		}
		delete_transient( 'wbdres_dashboard_stats' );
	}

	/**
	 * Flush rewrite rules after slug changes.
	 */
	public function maybe_flush() {
		if ( get_option( 'wbdres_flush_rewrite' ) ) {
			delete_option( 'wbdres_flush_rewrite' );
			flush_rewrite_rules();
		}
	}

	/**
	 * Register CPTs.
	 */
	public function register_post_types() {
		register_post_type(
			self::PROPERTY,
			array(
				'labels'          => array(
					'name'                  => _x( 'Properties', 'post type general name', 'wbd-real-estate-suite' ),
					'singular_name'         => _x( 'Property', 'post type singular name', 'wbd-real-estate-suite' ),
					'menu_name'             => __( 'Properties', 'wbd-real-estate-suite' ),
					'add_new'               => __( 'Add New Property', 'wbd-real-estate-suite' ),
					'add_new_item'          => __( 'Add New Property', 'wbd-real-estate-suite' ),
					'edit_item'             => __( 'Edit Property', 'wbd-real-estate-suite' ),
					'new_item'              => __( 'New Property', 'wbd-real-estate-suite' ),
					'view_item'             => __( 'View Property', 'wbd-real-estate-suite' ),
					'view_items'            => __( 'View Properties', 'wbd-real-estate-suite' ),
					'search_items'          => __( 'Search Properties', 'wbd-real-estate-suite' ),
					'not_found'             => __( 'No properties found.', 'wbd-real-estate-suite' ),
					'not_found_in_trash'    => __( 'No properties found in Trash.', 'wbd-real-estate-suite' ),
					'all_items'             => __( 'All Properties', 'wbd-real-estate-suite' ),
					'archives'              => __( 'Property Archives', 'wbd-real-estate-suite' ),
					'featured_image'        => __( 'Main photo', 'wbd-real-estate-suite' ),
					'set_featured_image'    => __( 'Set main photo', 'wbd-real-estate-suite' ),
					'remove_featured_image' => __( 'Remove main photo', 'wbd-real-estate-suite' ),
					'use_featured_image'    => __( 'Use as main photo', 'wbd-real-estate-suite' ),
					'item_published'        => __( 'Property published.', 'wbd-real-estate-suite' ),
					'item_updated'          => __( 'Property updated.', 'wbd-real-estate-suite' ),
				),
				'public'          => true,
				'show_in_menu'    => 'wbdres',
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-admin-home',
				'has_archive'     => Settings::get( 'archive_slug', 'properties' ),
				'rewrite'         => array(
					'slug'       => Settings::get( 'property_slug', 'property' ),
					'with_front' => false,
				),
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'custom-fields', 'revisions' ),
				'capability_type' => array( 'wbd_property', 'wbd_properties' ),
				'map_meta_cap'    => true,
				'template'        => array(
					array(
						'core/paragraph',
						array( 'placeholder' => __( 'Describe the property: highlights, neighbourhood, condition…', 'wbd-real-estate-suite' ) ),
					),
				),
			)
		);

		register_post_type(
			self::AGENT,
			array(
				'labels'          => array(
					'name'               => _x( 'Agents', 'post type general name', 'wbd-real-estate-suite' ),
					'singular_name'      => _x( 'Agent', 'post type singular name', 'wbd-real-estate-suite' ),
					'add_new'            => __( 'Add New Agent', 'wbd-real-estate-suite' ),
					'add_new_item'       => __( 'Add New Agent', 'wbd-real-estate-suite' ),
					'edit_item'          => __( 'Edit Agent', 'wbd-real-estate-suite' ),
					'view_item'          => __( 'View Agent', 'wbd-real-estate-suite' ),
					'search_items'       => __( 'Search Agents', 'wbd-real-estate-suite' ),
					'not_found'          => __( 'No agents found.', 'wbd-real-estate-suite' ),
					'not_found_in_trash' => __( 'No agents found in Trash.', 'wbd-real-estate-suite' ),
					'all_items'          => __( 'Agents', 'wbd-real-estate-suite' ),
					'featured_image'     => __( 'Profile photo', 'wbd-real-estate-suite' ),
					'set_featured_image' => __( 'Set profile photo', 'wbd-real-estate-suite' ),
				),
				'public'          => true,
				'show_in_menu'    => 'wbdres',
				'show_in_rest'    => true,
				'has_archive'     => false,
				'rewrite'         => array(
					'slug'       => Settings::get( 'agent_slug', 'agent' ),
					'with_front' => false,
				),
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
				'capability_type' => array( 'wbd_agent', 'wbd_agents' ),
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Register taxonomies.
	 */
	public function register_taxonomies() {
		$caps = array(
			'manage_terms' => 'manage_categories',
			'edit_terms'   => 'manage_categories',
			'delete_terms' => 'manage_categories',
			'assign_terms' => 'edit_wbd_properties',
		);

		$taxonomies = array(
			self::TYPE     => array(
				'slug'     => 'property-type',
				'name'     => __( 'Property Types', 'wbd-real-estate-suite' ),
				'singular' => __( 'Property Type', 'wbd-real-estate-suite' ),
			),
			self::STATUS   => array(
				'slug'     => 'property-status',
				'name'     => __( 'Property Statuses', 'wbd-real-estate-suite' ),
				'singular' => __( 'Property Status', 'wbd-real-estate-suite' ),
			),
			self::LOCATION => array(
				'slug'     => 'property-location',
				'name'     => __( 'Locations', 'wbd-real-estate-suite' ),
				'singular' => __( 'Location', 'wbd-real-estate-suite' ),
			),
			self::FEATURE  => array(
				'slug'     => 'property-feature',
				'name'     => __( 'Features & Amenities', 'wbd-real-estate-suite' ),
				'singular' => __( 'Feature', 'wbd-real-estate-suite' ),
			),
		);

		foreach ( $taxonomies as $taxonomy => $def ) {
			register_taxonomy(
				$taxonomy,
				self::PROPERTY,
				array(
					'labels'            => array(
						'name'          => $def['name'],
						'singular_name' => $def['singular'],
						'menu_name'     => $def['name'],
						/* translators: %s: taxonomy singular name. */
						'add_new_item'  => sprintf( __( 'Add New %s', 'wbd-real-estate-suite' ), $def['singular'] ),
						/* translators: %s: taxonomy singular name. */
						'edit_item'     => sprintf( __( 'Edit %s', 'wbd-real-estate-suite' ), $def['singular'] ),
						/* translators: %s: taxonomy plural name. */
						'search_items'  => sprintf( __( 'Search %s', 'wbd-real-estate-suite' ), $def['name'] ),
						/* translators: %s: taxonomy plural name. */
						'all_items'     => sprintf( __( 'All %s', 'wbd-real-estate-suite' ), $def['name'] ),
						/* translators: %s: taxonomy plural name. */
						'not_found'     => sprintf( __( 'No %s found.', 'wbd-real-estate-suite' ), $def['name'] ),
					),
					'public'            => true,
					'hierarchical'      => true,
					'show_in_rest'      => true,
					'show_admin_column' => self::FEATURE !== $taxonomy,
					'show_in_menu'      => false,
					'rewrite'           => array(
						'slug'         => $def['slug'],
						'with_front'   => false,
						'hierarchical' => self::LOCATION === $taxonomy,
					),
					'capabilities'      => $caps,
				)
			);
		}
	}

	/**
	 * Seed sensible default terms (only when a taxonomy is empty).
	 */
	public static function insert_default_terms() {
		$defaults = array(
			self::TYPE    => array(
				__( 'House', 'wbd-real-estate-suite' ),
				__( 'Apartment', 'wbd-real-estate-suite' ),
				__( 'Villa', 'wbd-real-estate-suite' ),
				__( 'Land', 'wbd-real-estate-suite' ),
				__( 'Commercial', 'wbd-real-estate-suite' ),
				__( 'Office', 'wbd-real-estate-suite' ),
			),
			self::STATUS  => array(
				__( 'For Sale', 'wbd-real-estate-suite' ),
				__( 'For Rent', 'wbd-real-estate-suite' ),
				__( 'Sold', 'wbd-real-estate-suite' ),
				__( 'Rented', 'wbd-real-estate-suite' ),
			),
			self::FEATURE => array(
				__( 'Air Conditioning', 'wbd-real-estate-suite' ),
				__( 'Parking', 'wbd-real-estate-suite' ),
				__( 'Swimming Pool', 'wbd-real-estate-suite' ),
				__( 'Garden', 'wbd-real-estate-suite' ),
				__( 'Balcony', 'wbd-real-estate-suite' ),
				__( 'Gym', 'wbd-real-estate-suite' ),
				__( '24/7 Security', 'wbd-real-estate-suite' ),
				__( 'Elevator', 'wbd-real-estate-suite' ),
				__( 'Furnished', 'wbd-real-estate-suite' ),
				__( 'Pet Friendly', 'wbd-real-estate-suite' ),
			),
		);
		foreach ( $defaults as $taxonomy => $terms ) {
			$existing = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'number'     => 1,
					'fields'     => 'ids',
				)
			);
			if ( ! empty( $existing ) && ! is_wp_error( $existing ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				wp_insert_term( $term, $taxonomy );
			}
		}
	}
}
