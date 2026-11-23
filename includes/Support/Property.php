<?php
/**
 * Property read model.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Support;

use Wbdres\RealEstate\PostTypes\Registrar;

defined( 'ABSPATH' ) || exit;

/**
 * Convenience accessor around a property post and its meta.
 */
final class Property {

	/**
	 * Post.
	 *
	 * @var \WP_Post
	 */
	public $post;

	/**
	 * Constructor.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function __construct( \WP_Post $post ) {
		$this->post = $post;
	}

	/**
	 * Factory.
	 *
	 * @param int|\WP_Post|null $post Post or ID.
	 * @return Property|null
	 */
	public static function get( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || Registrar::PROPERTY !== $post->post_type ) {
			return null;
		}
		return new self( $post );
	}

	/**
	 * Raw meta.
	 *
	 * @param string $key Key without `_wbdres_` prefix.
	 * @return mixed
	 */
	public function meta( $key ) {
		return get_post_meta( $this->post->ID, '_wbdres_' . $key, true );
	}

	/**
	 * ID.
	 *
	 * @return int
	 */
	public function id() {
		return (int) $this->post->ID;
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function title() {
		return get_the_title( $this->post );
	}

	/**
	 * Permalink.
	 *
	 * @return string
	 */
	public function url() {
		return get_permalink( $this->post );
	}

	/**
	 * Formatted price.
	 *
	 * @return string
	 */
	public function price() {
		return Format::price( (float) $this->meta( 'price' ), (string) $this->meta( 'price_suffix' ), (bool) $this->meta( 'price_on_request' ) );
	}

	/**
	 * Is featured.
	 *
	 * @return bool
	 */
	public function is_featured() {
		return (bool) $this->meta( 'featured' );
	}

	/**
	 * First term name of a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return \WP_Term|null
	 */
	public function term( $taxonomy ) {
		$terms = get_the_terms( $this->post, $taxonomy );
		return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	}

	/**
	 * All terms of a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return \WP_Term[]
	 */
	public function terms( $taxonomy ) {
		$terms = get_the_terms( $this->post, $taxonomy );
		return ( $terms && ! is_wp_error( $terms ) ) ? $terms : array();
	}

	/**
	 * Display address: street address, falling back to location terms.
	 *
	 * @return string
	 */
	public function address() {
		$address = (string) $this->meta( 'address' );
		if ( '' !== $address ) {
			return $address;
		}
		$location = $this->term( Registrar::LOCATION );
		if ( ! $location ) {
			return '';
		}
		$names  = array( $location->name );
		$parent = $location->parent;
		while ( $parent ) {
			$p = get_term( $parent, Registrar::LOCATION );
			if ( ! $p || is_wp_error( $p ) ) {
				break;
			}
			$names[] = $p->name;
			$parent  = $p->parent;
		}
		return implode( ', ', $names );
	}

	/**
	 * Image IDs: featured image first, then gallery.
	 *
	 * @return int[]
	 */
	public function image_ids() {
		$ids   = array();
		$thumb = (int) get_post_thumbnail_id( $this->post );
		if ( $thumb ) {
			$ids[] = $thumb;
		}
		$gallery = $this->meta( 'gallery' );
		if ( is_array( $gallery ) ) {
			foreach ( $gallery as $id ) {
				$id = (int) $id;
				if ( $id && ! in_array( $id, $ids, true ) && wp_attachment_is_image( $id ) ) {
					$ids[] = $id;
				}
			}
		}
		return $ids;
	}

	/**
	 * Key specifications for display.
	 *
	 * @param bool $all Include extended specs.
	 * @return array[] Each: key, label, value, icon.
	 */
	public function specs( $all = false ) {
		$specs = array();
		$beds  = (int) $this->meta( 'bedrooms' );
		$baths = (float) $this->meta( 'bathrooms' );
		$area  = (float) $this->meta( 'area' );

		if ( $beds ) {
			$specs[] = array(
				'key'   => 'bedrooms',
				/* translators: number of bedrooms label */
				'label' => _n( 'Bedroom', 'Bedrooms', $beds, 'wbd-real-estate-suite' ),
				'short' => _n( 'Bed', 'Beds', $beds, 'wbd-real-estate-suite' ),
				'value' => (string) $beds,
				'icon'  => 'bed',
			);
		}
		if ( $baths ) {
			$specs[] = array(
				'key'   => 'bathrooms',
				'label' => _n( 'Bathroom', 'Bathrooms', (int) ceil( $baths ), 'wbd-real-estate-suite' ),
				'short' => _n( 'Bath', 'Baths', (int) ceil( $baths ), 'wbd-real-estate-suite' ),
				'value' => Format::half( $baths ),
				'icon'  => 'bath',
			);
		}
		if ( $area ) {
			$specs[] = array(
				'key'   => 'area',
				'label' => __( 'Area', 'wbd-real-estate-suite' ),
				'value' => Format::area( $area ),
				'icon'  => 'area',
			);
		}
		if ( ! $all ) {
			return $specs;
		}

		$garages = (int) $this->meta( 'garages' );
		if ( $garages ) {
			$specs[] = array(
				'key'   => 'garages',
				'label' => _n( 'Garage', 'Garages', $garages, 'wbd-real-estate-suite' ),
				'value' => (string) $garages,
				'icon'  => 'garage',
			);
		}
		$land = (float) $this->meta( 'land_area' );
		if ( $land ) {
			$specs[] = array(
				'key'   => 'land_area',
				'label' => __( 'Land area', 'wbd-real-estate-suite' ),
				'value' => Format::area( $land ),
				'icon'  => 'land',
			);
		}
		$year = (int) $this->meta( 'year_built' );
		if ( $year ) {
			$specs[] = array(
				'key'   => 'year_built',
				'label' => __( 'Year built', 'wbd-real-estate-suite' ),
				'value' => (string) $year,
				'icon'  => 'calendar',
			);
		}
		$type = $this->term( Registrar::TYPE );
		if ( $type ) {
			$specs[] = array(
				'key'   => 'type',
				'label' => __( 'Type', 'wbd-real-estate-suite' ),
				'value' => $type->name,
				'icon'  => 'home',
			);
		}
		$ref = (string) $this->meta( 'reference' );
		if ( '' !== $ref ) {
			$specs[] = array(
				'key'   => 'reference',
				'label' => __( 'Reference', 'wbd-real-estate-suite' ),
				'value' => $ref,
				'icon'  => 'tag',
			);
		}
		return $specs;
	}

	/**
	 * Agent post, if assigned and published.
	 *
	 * @return \WP_Post|null
	 */
	public function agent() {
		$agent_id = (int) $this->meta( 'agent_id' );
		if ( ! $agent_id ) {
			return null;
		}
		$agent = get_post( $agent_id );
		return ( $agent && Registrar::AGENT === $agent->post_type && 'publish' === $agent->post_status ) ? $agent : null;
	}

	/**
	 * Compact array for REST / JS consumers (public data only).
	 *
	 * @return array
	 */
	public function to_array() {
		$status = $this->term( Registrar::STATUS );
		$type   = $this->term( Registrar::TYPE );
		$img    = $this->image_ids();
		return array(
			'id'        => $this->id(),
			'title'     => html_entity_decode( $this->title(), ENT_QUOTES, 'UTF-8' ),
			'url'       => $this->url(),
			'price'     => $this->price(),
			'priceRaw'  => (float) $this->meta( 'price' ),
			'address'   => $this->address(),
			'status'    => $status ? $status->name : '',
			'type'      => $type ? $type->name : '',
			'bedrooms'  => (int) $this->meta( 'bedrooms' ),
			'bathrooms' => Format::half( (float) $this->meta( 'bathrooms' ) ),
			'area'      => Format::area( (float) $this->meta( 'area' ) ),
			'landArea'  => Format::area( (float) $this->meta( 'land_area' ) ),
			'garages'   => (int) $this->meta( 'garages' ),
			'yearBuilt' => (int) $this->meta( 'year_built' ),
			'features'  => wp_list_pluck( $this->terms( Registrar::FEATURE ), 'name' ),
			'image'     => $img ? (string) wp_get_attachment_image_url( $img[0], 'medium_large' ) : '',
			'imageAlt'  => $img ? (string) get_post_meta( $img[0], '_wp_attachment_image_alt', true ) : '',
		);
	}
}
