<?php
/**
 * JSON-LD structured data for single properties and agents.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Schema;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;
use Wbdres\RealEstate\Support\Property;

defined( 'ABSPATH' ) || exit;

/**
 * Outputs schema.org RealEstateListing / RealEstateAgent.
 */
final class StructuredData {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'wp_head', array( $this, 'output' ), 30 );
	}

	/**
	 * Build the property graph.
	 *
	 * @param Property $p Property.
	 * @return array
	 */
	public static function property_schema( Property $p ) {
		$images = array();
		foreach ( array_slice( $p->image_ids(), 0, 6 ) as $id ) {
			$src = wp_get_attachment_image_url( $id, 'large' );
			if ( $src ) {
				$images[] = $src;
			}
		}
		$type_term = $p->term( Registrar::TYPE );
		$slug      = $type_term ? $type_term->slug : '';
		$map       = array(
			'house'     => 'SingleFamilyResidence',
			'villa'     => 'House',
			'apartment' => 'Apartment',
			'condo'     => 'Apartment',
		);
		$place     = array(
			'@type' => isset( $map[ $slug ] ) ? $map[ $slug ] : 'Accommodation',
			'name'  => wp_strip_all_tags( $p->title() ),
		);
		$beds      = (int) $p->meta( 'bedrooms' );
		$baths     = (float) $p->meta( 'bathrooms' );
		$area      = (float) $p->meta( 'area' );
		if ( $beds ) {
			$place['numberOfBedrooms'] = $beds;
			$place['numberOfRooms']    = $beds;
		}
		if ( $baths ) {
			$place['numberOfBathroomsTotal'] = $baths;
		}
		if ( $area ) {
			$codes              = array(
				'sqft'    => 'FTK',
				'sqm'     => 'MTK',
				'acre'    => 'ACR',
				'hectare' => 'HAR',
			);
			$unit               = Settings::get( 'area_unit', 'sqft' );
			$place['floorSize'] = array_filter(
				array(
					'@type'    => 'QuantitativeValue',
					'value'    => $area,
					'unitCode' => isset( $codes[ $unit ] ) ? $codes[ $unit ] : null,
				)
			);
		}
		$year = (int) $p->meta( 'year_built' );
		if ( $year ) {
			$place['yearBuilt'] = $year;
		}
		$loc     = $p->term( Registrar::LOCATION );
		$address = array_filter(
			array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => (string) $p->meta( 'address' ),
				'postalCode'      => (string) $p->meta( 'postal_code' ),
				'addressCountry'  => (string) $p->meta( 'country' ),
				'addressLocality' => $loc ? $loc->name : '',
			)
		);
		if ( count( $address ) > 1 ) {
			$place['address'] = $address;
		}
		$lat = (float) $p->meta( 'lat' );
		$lng = (float) $p->meta( 'lng' );
		if ( ( $lat || $lng ) && ! $p->meta( 'hide_map' ) ) {
			$place['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => $lat,
				'longitude' => $lng,
			);
		}
		$features = wp_list_pluck( $p->terms( Registrar::FEATURE ), 'name' );
		if ( $features ) {
			$place['amenityFeature'] = array_map(
				static function ( $name ) {
					return array(
						'@type' => 'LocationFeatureSpecification',
						'name'  => $name,
						'value' => true,
					);
				},
				$features
			);
		}

		$data  = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'RealEstateListing',
			'@id'         => $p->url() . '#listing',
			'url'         => $p->url(),
			'name'        => wp_strip_all_tags( $p->title() ),
			'description' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $p->post ) ), 50 ),
			'datePosted'  => get_the_date( 'c', $p->post ),
			'image'       => $images,
			'about'       => $place,
		);
		$price = (float) $p->meta( 'price' );
		if ( $price > 0 && ! $p->meta( 'price_on_request' ) ) {
			$status         = $p->term( Registrar::STATUS );
			$sold           = $status && in_array( $status->slug, array( 'sold', 'rented' ), true );
			$data['offers'] = array(
				'@type'            => 'Offer',
				'price'            => $price,
				'priceCurrency'    => Settings::get( 'currency_code', 'USD' ),
				'availability'     => $sold ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
				'businessFunction' => ( $status && false !== strpos( $status->slug, 'rent' ) ) ? 'http://purl.org/goodrelations/v1#LeaseOut' : 'http://purl.org/goodrelations/v1#Sell',
			);
		}
		$agent = $p->agent();
		if ( $agent ) {
			$data['provider'] = array_filter(
				array(
					'@type'     => 'RealEstateAgent',
					'name'      => get_the_title( $agent ),
					'url'       => get_permalink( $agent ),
					'telephone' => (string) get_post_meta( $agent->ID, '_wbdres_agent_phone', true ),
				)
			);
		}

		/**
		 * Filter the property JSON-LD.
		 *
		 * @param array    $data Data.
		 * @param Property $p    Property.
		 */
		return apply_filters( 'wbdres_property_schema', array_filter( $data ), $p );
	}

	/**
	 * Print JSON-LD.
	 */
	public function output() {
		if ( ! Settings::get( 'enable_schema', 1 ) ) {
			return;
		}
		$data = null;
		if ( is_singular( Registrar::PROPERTY ) ) {
			$p = Property::get( get_queried_object_id() );
			if ( $p && ! post_password_required( $p->post ) ) {
				$data = self::property_schema( $p );
			}
		} elseif ( is_singular( Registrar::AGENT ) ) {
			$agent = get_post( get_queried_object_id() );
			$data  = array_filter(
				array(
					'@context'  => 'https://schema.org',
					'@type'     => 'RealEstateAgent',
					'name'      => get_the_title( $agent ),
					'url'       => get_permalink( $agent ),
					'image'     => get_the_post_thumbnail_url( $agent, 'medium' ),
					'telephone' => (string) get_post_meta( $agent->ID, '_wbdres_agent_phone', true ),
				)
			);
		}
		if ( $data ) {
			echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . "</script>\n";
		}
	}
}
