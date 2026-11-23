<?php
/**
 * Removable sample content for first-time exploration.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\Leads\LeadRepository;
use Wbdres\RealEstate\PostTypes\Registrar;

defined( 'ABSPATH' ) || exit;

/**
 * SampleData.
 */
final class SampleData {

	/**
	 * Get or create a term by name.
	 *
	 * @param string $name     Name.
	 * @param string $taxonomy Taxonomy.
	 * @param int    $parent_id Parent.
	 * @return int
	 */
	private static function term( $name, $taxonomy, $parent_id = 0 ) {
		$t = term_exists( $name, $taxonomy, $parent_id );
		if ( ! $t ) {
			$t = wp_insert_term( $name, $taxonomy, array( 'parent' => $parent_id ) );
		}
		return is_wp_error( $t ) ? 0 : (int) $t['term_id'];
	}

	/**
	 * Install.
	 */
	public static function install() {
		if ( get_option( 'wbdres_sample_installed' ) ) {
			return;
		}
		$agents = array();
		foreach ( array(
			array( 'Sofia Martins', 'Senior Property Consultant', '+351 912 345 678', 'Lisbon Homes Realty', 'English, Portuguese, Spanish' ),
			array( 'Daniel Okafor', 'Residential Sales Agent', '+1 512 555 0142', 'Hill Country Realty', 'English' ),
			array( 'Aisha Rahman', 'Luxury Rentals Specialist', '+971 50 123 4567', 'Marina Estates', 'English, Arabic, Bengali' ),
		) as $a ) {
			$id = wp_insert_post(
				array(
					'post_type'    => Registrar::AGENT,
					'post_status'  => 'publish',
					'post_title'   => $a[0],
					'post_excerpt' => sprintf( 'Helping families and investors find the right home for over 10 years. %s.', $a[1] ),
					'post_content' => '<!-- wp:paragraph --><p>Local market expert focused on honest advice, fast responses and smooth closings.</p><!-- /wp:paragraph -->',
				)
			);
			update_post_meta( $id, '_wbdres_agent_title', $a[1] );
			update_post_meta( $id, '_wbdres_agent_phone', $a[2] );
			update_post_meta( $id, '_wbdres_agent_whatsapp', $a[2] );
			update_post_meta( $id, '_wbdres_agent_agency', $a[3] );
			update_post_meta( $id, '_wbdres_agent_languages', $a[4] );
			update_post_meta( $id, '_wbdres_agent_email', get_option( 'admin_email' ) );
			update_post_meta( $id, '_wbdres_sample', 1 );
			$agents[] = $id;
		}

		$portugal = self::term( 'Portugal', Registrar::LOCATION );
		$usa      = self::term( 'United States', Registrar::LOCATION );
		$uae      = self::term( 'United Arab Emirates', Registrar::LOCATION );
		$loc      = array(
			'lisbon' => self::term( 'Lisbon', Registrar::LOCATION, $portugal ),
			'porto'  => self::term( 'Porto', Registrar::LOCATION, $portugal ),
			'austin' => self::term( 'Austin', Registrar::LOCATION, $usa ),
			'dubai'  => self::term( 'Dubai', Registrar::LOCATION, $uae ),
		);

		$props = array(
			array( 'Emerald Valley Terrace Home', 'House', 'For Sale', 'austin', 685000, '', 4, 3, 2450, 2018, '1204 Oak Ridge Dr, Austin, TX', 30.2849, -97.7341, 1, 1, array( 'Garden', 'Parking', 'Air Conditioning', 'Swimming Pool' ) ),
			array( 'Maplewood Garden House', 'House', 'For Sale', 'austin', 529000, '', 3, 2.5, 1980, 2012, '88 Maplewood Ln, Austin, TX', 30.3072, -97.7560, 0, 1, array( 'Garden', 'Parking', 'Pet Friendly' ) ),
			array( 'Silver Creek Villa', 'Villa', 'For Sale', 'lisbon', 1250000, '', 5, 4, 3600, 2020, 'Rua das Flores 21, Cascais', 38.6979, -9.4215, 1, 0, array( 'Swimming Pool', 'Garden', '24/7 Security', 'Air Conditioning' ) ),
			array( 'Alfama Riverside Apartment', 'Apartment', 'For Rent', 'lisbon', 2400, '/month', 2, 1, 980, 1925, 'Rua de São Miguel 14, Lisbon', 38.7115, -9.1300, 0, 0, array( 'Balcony', 'Furnished', 'Elevator' ) ),
			array( 'Ribeira Loft with River View', 'Apartment', 'For Rent', 'porto', 1650, '/month', 1, 1, 720, 2016, 'Cais da Ribeira 9, Porto', 41.1405, -8.6131, 0, 0, array( 'Balcony', 'Furnished' ) ),
			array( 'Marina Heights Penthouse', 'Apartment', 'For Rent', 'dubai', 14500, '/month', 3, 3.5, 2900, 2019, 'Dubai Marina, Tower 7', 25.0805, 55.1403, 1, 2, array( 'Swimming Pool', 'Gym', '24/7 Security', 'Parking', 'Elevator' ) ),
			array( 'Palm Grove Family Villa', 'Villa', 'For Sale', 'dubai', 3950000, '', 6, 6, 6200, 2021, 'Frond F, Palm Jumeirah', 25.1124, 55.1390, 1, 2, array( 'Swimming Pool', 'Garden', 'Gym', '24/7 Security', 'Parking' ) ),
			array( 'Downtown Office Suite', 'Office', 'For Rent', 'austin', 5200, '/month', 0, 2, 2100, 2015, '500 Congress Ave, Austin, TX', 30.2676, -97.7428, 0, 1, array( 'Parking', 'Elevator', 'Air Conditioning' ) ),
			array( 'Hillside Building Plot', 'Land', 'For Sale', 'porto', 145000, '', 0, 0, 0, 0, 'Vila Nova de Gaia', 41.1239, -8.6118, 0, 0, array() ),
		);
		$ids   = array();
		foreach ( $props as $i => $p ) {
			$id = wp_insert_post(
				array(
					'post_type'    => Registrar::PROPERTY,
					'post_status'  => 'publish',
					'post_title'   => $p[0],
					'post_excerpt' => 'Bright, well-kept property in a sought-after neighbourhood close to schools, shops and transport.',
					'post_content' => '<!-- wp:paragraph --><p>This sample listing shows how your descriptions appear. Highlight the layout, recent renovations, natural light, outdoor space and what makes the neighbourhood special.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Replace this text and add your own photos from the property editor.</p><!-- /wp:paragraph -->',
					'post_date'    => gmdate( 'Y-m-d H:i:s', time() - ( $i * 3 + 1 ) * DAY_IN_SECONDS ),
				)
			);
			wp_set_object_terms( $id, array( self::term( $p[1], Registrar::TYPE ) ), Registrar::TYPE );
			wp_set_object_terms( $id, array( self::term( $p[2], Registrar::STATUS ) ), Registrar::STATUS );
			wp_set_object_terms( $id, array( $loc[ $p[3] ] ), Registrar::LOCATION );
			$features = array();
			foreach ( $p[15] as $f ) {
				$features[] = self::term( $f, Registrar::FEATURE );
			}
			wp_set_object_terms( $id, $features, Registrar::FEATURE );
			$meta = array(
				'_wbdres_price'        => $p[4],
				'_wbdres_price_suffix' => $p[5],
				'_wbdres_bedrooms'     => $p[6],
				'_wbdres_bathrooms'    => $p[7],
				'_wbdres_area'         => $p[8],
				'_wbdres_year_built'   => $p[9],
				'_wbdres_address'      => $p[10],
				'_wbdres_lat'          => $p[11],
				'_wbdres_lng'          => $p[12],
				'_wbdres_featured'     => $p[13],
				'_wbdres_agent_id'     => $agents[ $p[14] ],
				'_wbdres_reference'    => 'WBD-' . ( 1001 + $i ),
				'_wbdres_garages'      => $p[6] > 2 ? 2 : 0,
				'_wbdres_views'        => wp_rand( 20, 400 ),
				'_wbdres_sample'       => 1,
			);
			foreach ( $meta as $k => $v ) {
				update_post_meta( $id, $k, $v );
			}
			$ids[] = $id;
		}

		$people   = array( 'Emma Johnson', 'Lucas Silva', 'Fatima Al Zahra', 'Noah Williams', 'Mia Chen', 'Omar Haddad', 'Olivia Brown', 'Rafael Costa', 'Priya Sharma', 'James Miller', 'Sara Ahmed', 'Leo Martin' );
		$statuses = array( 'new', 'new', 'new', 'contacted', 'contacted', 'qualified', 'viewing', 'viewing', 'negotiation', 'won', 'won', 'lost' );
		$types    = array( 'inquiry', 'viewing', 'inquiry', 'contact', 'inquiry', 'viewing', 'viewing', 'inquiry', 'inquiry', 'inquiry', 'contact', 'inquiry' );
		$leads    = array();
		foreach ( $people as $i => $name ) {
			$pid     = $ids[ $i % count( $ids ) ];
			$lead_id = LeadRepository::insert(
				array(
					'type'           => $types[ $i ],
					'status'         => $statuses[ $i ],
					'name'           => $name,
					'email'          => sanitize_title( $name ) . '@example.com',
					'phone'          => '+1 555 01' . str_pad( (string) $i, 2, '0', STR_PAD_LEFT ),
					'message'        => 'Hello, I am interested in this property. Is it still available and can we arrange a visit?',
					'property_id'    => $pid,
					'agent_id'       => (int) get_post_meta( $pid, '_wbdres_agent_id', true ),
					'preferred_date' => 'viewing' === $types[ $i ] ? gmdate( 'Y-m-d', time() + ( $i + 2 ) * DAY_IN_SECONDS ) : null,
					'preferred_time' => 'viewing' === $types[ $i ] ? 'afternoon' : '',
					'consent'        => 1,
				)
			);
			// Spread creation dates over the last two months.
			global $wpdb;
			$wpdb->update( \Wbdres\RealEstate\Install\Installer::leads_table(), array( 'created_at' => gmdate( 'Y-m-d H:i:s', time() - $i * 4 * DAY_IN_SECONDS - $i * HOUR_IN_SECONDS ) ), array( 'id' => $lead_id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			LeadRepository::add_note( $lead_id, 'Sample lead.', 'system' );
			$leads[] = $lead_id;
		}
		wp_cache_delete( 'counts', 'wbdres_leads' );
		update_option(
			'wbdres_sample_installed',
			array(
				'leads' => $leads,
			),
			false
		);
	}

	/**
	 * Remove.
	 */
	public static function remove() {
		$posts = get_posts(
			array(
				'post_type'      => array( Registrar::PROPERTY, Registrar::AGENT ),
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_key'       => '_wbdres_sample', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		foreach ( $posts as $id ) {
			wp_delete_post( $id, true );
		}
		$data = get_option( 'wbdres_sample_installed' );
		if ( is_array( $data ) && ! empty( $data['leads'] ) ) {
			LeadRepository::delete( $data['leads'] );
		}
		delete_option( 'wbdres_sample_installed' );
	}
}
