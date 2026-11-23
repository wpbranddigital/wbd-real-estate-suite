<?php
/**
 * Tools: CSV import/export with dry-run preview and undo, sample content, demo pages, system status.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\Install\Installer;
use Wbdres\RealEstate\Leads\LeadRepository;
use Wbdres\RealEstate\Meta\PropertyMeta;
use Wbdres\RealEstate\PostTypes\Registrar;

defined( 'ABSPATH' ) || exit;

/**
 * ToolsPage.
 */
final class ToolsPage {

	const MAX_ROWS = 1000;

	/**
	 * Hooks.
	 */
	public function register() {
		foreach ( array( 'import', 'export', 'undo', 'sample', 'sample_remove', 'pages' ) as $action ) {
			add_action( 'admin_post_wbdres_tool_' . $action, array( $this, 'handle_' . $action ) );
		}
	}

	/**
	 * Column definitions: CSV header => [meta key | special, label].
	 *
	 * @return array
	 */
	public static function columns() {
		return array(
			'title'            => 'post_title',
			'description'      => 'post_content',
			'excerpt'          => 'post_excerpt',
			'post_status'      => 'post_status',
			'listing_status'   => 'tax:' . Registrar::STATUS,
			'type'             => 'tax:' . Registrar::TYPE,
			'location'         => 'tax:' . Registrar::LOCATION,
			'features'         => 'tax:' . Registrar::FEATURE,
			'price'            => '_wbdres_price',
			'price_suffix'     => '_wbdres_price_suffix',
			'price_on_request' => '_wbdres_price_on_request',
			'bedrooms'         => '_wbdres_bedrooms',
			'bathrooms'        => '_wbdres_bathrooms',
			'area'             => '_wbdres_area',
			'land_area'        => '_wbdres_land_area',
			'garages'          => '_wbdres_garages',
			'year_built'       => '_wbdres_year_built',
			'reference'        => '_wbdres_reference',
			'address'          => '_wbdres_address',
			'postal_code'      => '_wbdres_postal_code',
			'country'          => '_wbdres_country',
			'lat'              => '_wbdres_lat',
			'lng'              => '_wbdres_lng',
			'video_url'        => '_wbdres_video_url',
			'tour_url'         => '_wbdres_tour_url',
			'featured'         => '_wbdres_featured',
			'agent'            => 'agent',
			'images'           => 'images',
		);
	}

	/**
	 * Header aliases.
	 *
	 * @return array
	 */
	private static function aliases() {
		return array(
			'name'      => 'title',
			'content'   => 'description',
			'status'    => 'listing_status',
			'category'  => 'type',
			'city'      => 'location',
			'amenities' => 'features',
			'beds'      => 'bedrooms',
			'baths'     => 'bathrooms',
			'size'      => 'area',
			'sqft'      => 'area',
			'latitude'  => 'lat',
			'longitude' => 'lng',
			'ref'       => 'reference',
			'id'        => 'reference',
			'photos'    => 'images',
			'image'     => 'images',
		);
	}

	/**
	 * Guard.
	 *
	 * @param string $nonce Nonce action.
	 */
	private function guard( $nonce ) {
		if ( ! current_user_can( 'manage_wbdres' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'wbd-real-estate-suite' ), 403 );
		}
		check_admin_referer( $nonce );
	}

	/**
	 * Redirect back with a notice.
	 *
	 * @param string $msg Message key.
	 * @param array  $extra Extra args.
	 */
	private function back( $msg, array $extra = array() ) {
		wp_safe_redirect( add_query_arg( array_merge( array( 'wbdres_msg' => $msg ), $extra ), admin_url( 'admin.php?page=wbdres-tools' ) ) );
		exit;
	}

	/**
	 * CSV import (dry run or real), with duplicate detection by reference.
	 */
	public function handle_import() {
		$this->guard( 'wbdres_tool_import' );
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in guard().
		if ( empty( $_FILES['csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$this->back( 'nofile' );
		}
		$name = isset( $_FILES['csv']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['csv']['name'] ) ) : '';
		if ( 'csv' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) || ! isset( $_FILES['csv']['size'] ) || (int) $_FILES['csv']['size'] > 10 * MB_IN_BYTES ) {
			$this->back( 'badfile' );
		}
		$dry       = ! empty( $_POST['dry_run'] );
		$dupes     = isset( $_POST['duplicates'] ) && 'update' === $_POST['duplicates'] ? 'update' : 'skip';
		$do_images = ! empty( $_POST['download_images'] );
		$status    = isset( $_POST['default_status'] ) && 'publish' === $_POST['default_status'] ? 'publish' : 'draft';

		$handle = fopen( $_FILES['csv']['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reading the uploaded temp file.
		if ( ! $handle ) {
			$this->back( 'badfile' );
		}
		$header = fgetcsv( $handle, 0, ',', '"', '\\' );
		if ( ! $header ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$this->back( 'badfile' );
		}
		$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header[0] );
		$cols      = self::columns();
		$aliases   = self::aliases();
		$map       = array();
		$unknown   = array();
		foreach ( $header as $i => $h ) {
			$key = sanitize_key( str_replace( array( ' ', '-' ), '_', strtolower( trim( (string) $h ) ) ) );
			$key = isset( $aliases[ $key ] ) ? $aliases[ $key ] : $key;
			if ( isset( $cols[ $key ] ) ) {
				$map[ $i ] = $key;
			} elseif ( '' !== $key ) {
				$unknown[] = $key;
			}
		}
		if ( ! in_array( 'title', $map, true ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$this->back( 'notitle' );
		}

		$batch  = 'b' . gmdate( 'YmdHis' ) . wp_rand( 100, 999 );
		$report = array(
			'dry'     => $dry,
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors'  => array(),
			'preview' => array(),
			'unknown' => $unknown,
			'batch'   => $batch,
			'mapped'  => array_values( $map ),
		);
		$row_no = 1;
		while ( true ) {
			$row = fgetcsv( $handle, 0, ',', '"', '\\' );
			if ( false === $row ) {
				break;
			}
			++$row_no;
			if ( $row_no > self::MAX_ROWS + 1 ) {
				/* translators: %d: max rows. */
				$report['errors'][] = sprintf( __( 'Stopped after %d rows. Split larger files.', 'wbd-real-estate-suite' ), self::MAX_ROWS );
				break;
			}
			if ( ! array_filter( $row, 'strlen' ) ) {
				continue;
			}
			$data = array();
			foreach ( $map as $i => $key ) {
				$data[ $key ] = isset( $row[ $i ] ) ? trim( (string) $row[ $i ] ) : '';
			}
			$result = $this->import_row( $data, $dry, $dupes, $do_images, $status, $batch );
			if ( is_wp_error( $result ) ) {
				/* translators: 1: row number, 2: error. */
				$report['errors'][] = sprintf( __( 'Row %1$d: %2$s', 'wbd-real-estate-suite' ), $row_no, $result->get_error_message() );
				++$report['skipped'];
				continue;
			}
			++$report[ $result ];
			if ( $dry && count( $report['preview'] ) < 15 ) {
				$report['preview'][] = array(
					'title'  => mb_substr( sanitize_text_field( $data['title'] ?? '' ), 0, 80 ),
					'price'  => $data['price'] ?? '',
					'type'   => $data['type'] ?? '',
					'action' => $result,
				);
			}
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		$report['errors'] = array_slice( $report['errors'], 0, 50 );

		if ( ! $dry && $report['created'] ) {
			$batches = get_option( 'wbdres_import_batches', array() );
			$batches = is_array( $batches ) ? $batches : array();
			array_unshift(
				$batches,
				array(
					'id'    => $batch,
					'date'  => time(),
					'count' => $report['created'],
					'file'  => $name,
				)
			);
			update_option( 'wbdres_import_batches', array_slice( $batches, 0, 10 ), false );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		set_transient( 'wbdres_import_report_' . get_current_user_id(), $report, HOUR_IN_SECONDS );
		delete_transient( 'wbdres_dashboard_stats' );
		$this->back( 'imported' );
	}

	/**
	 * Import one row.
	 *
	 * @param array  $d         Row data.
	 * @param bool   $dry       Dry run.
	 * @param string $dupes     skip|update.
	 * @param bool   $do_images Download images.
	 * @param string $status    Default post status.
	 * @param string $batch     Batch ID.
	 * @return string|\WP_Error created|updated|skipped.
	 */
	private function import_row( array $d, $dry, $dupes, $do_images, $status, $batch ) {
		$title = mb_substr( sanitize_text_field( $d['title'] ?? '' ), 0, 200 );
		if ( '' === $title ) {
			return new \WP_Error( 'title', __( 'Missing title.', 'wbd-real-estate-suite' ) );
		}
		foreach ( array( 'price', 'area', 'bedrooms', 'bathrooms', 'lat', 'lng', 'land_area' ) as $num ) {
			if ( isset( $d[ $num ] ) && '' !== $d[ $num ] && ! is_numeric( preg_replace( '/[\s,]/', '', $d[ $num ] ) ) ) {
				/* translators: %s: column name. */
				return new \WP_Error( 'num', sprintf( __( '"%s" must be a number.', 'wbd-real-estate-suite' ), $num ) );
			}
		}

		$existing = 0;
		if ( ! empty( $d['reference'] ) ) {
			$found    = get_posts(
				array(
					'post_type'      => Registrar::PROPERTY,
					'post_status'    => 'any',
					'fields'         => 'ids',
					'posts_per_page' => 1,
					'meta_key'       => '_wbdres_reference', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => sanitize_text_field( $d['reference'] ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
			$existing = $found ? (int) $found[0] : 0;
		}
		if ( $existing && 'skip' === $dupes ) {
			return 'skipped';
		}
		if ( $dry ) {
			return $existing ? 'updated' : 'created';
		}

		$post_status = isset( $d['post_status'] ) && in_array( $d['post_status'], array( 'publish', 'draft', 'pending', 'private' ), true ) ? $d['post_status'] : $status;
		$postarr     = array(
			'post_type'    => Registrar::PROPERTY,
			'post_title'   => $title,
			'post_content' => isset( $d['description'] ) ? wp_kses_post( $d['description'] ) : '',
			'post_excerpt' => isset( $d['excerpt'] ) ? sanitize_textarea_field( $d['excerpt'] ) : '',
			'post_status'  => $post_status,
		);
		if ( $existing ) {
			$postarr['ID'] = $existing;
			if ( ! isset( $d['description'] ) ) {
				unset( $postarr['post_content'] );
			}
			if ( ! isset( $d['excerpt'] ) ) {
				unset( $postarr['post_excerpt'] );
			}
			$post_id = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$schema = PropertyMeta::schema();
		foreach ( self::columns() as $col => $target ) {
			if ( ! isset( $d[ $col ] ) || 0 !== strpos( $target, '_wbdres_' ) ) {
				continue;
			}
			$value = $d[ $col ];
			if ( 'boolean' === $schema[ $target ][0] ) {
				$value = in_array( strtolower( $value ), array( '1', 'yes', 'true', 'y' ), true );
			} elseif ( in_array( $schema[ $target ][0], array( 'number', 'integer' ), true ) ) {
				$value = preg_replace( '/[\s,]/', '', $value );
			}
			update_post_meta( $post_id, $target, call_user_func( $schema[ $target ][2], $value ) );
		}
		foreach ( self::columns() as $col => $target ) {
			if ( ! isset( $d[ $col ] ) || 0 !== strpos( $target, 'tax:' ) || '' === $d[ $col ] ) {
				continue;
			}
			$taxonomy = substr( $target, 4 );
			$names    = array_filter( array_map( 'trim', explode( '|', $d[ $col ] ) ) );
			$ids      = array();
			foreach ( $names as $term_name ) {
				$term_name = mb_substr( sanitize_text_field( $term_name ), 0, 100 );
				$term      = term_exists( $term_name, $taxonomy );
				if ( ! $term ) {
					$term = wp_insert_term( $term_name, $taxonomy );
				}
				if ( ! is_wp_error( $term ) && $term ) {
					$ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
				}
			}
			wp_set_object_terms( $post_id, $ids, $taxonomy );
		}
		if ( ! empty( $d['agent'] ) ) {
			$agent_id = is_numeric( $d['agent'] ) ? absint( $d['agent'] ) : 0;
			if ( ! $agent_id ) {
				$agent    = get_posts(
					array(
						'post_type'      => Registrar::AGENT,
						'title'          => sanitize_text_field( $d['agent'] ),
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'post_status'    => 'any',
					)
				);
				$agent_id = $agent ? (int) $agent[0] : 0;
			}
			if ( $agent_id && Registrar::AGENT === get_post_type( $agent_id ) ) {
				update_post_meta( $post_id, '_wbdres_agent_id', $agent_id );
			}
		}
		if ( $do_images && ! empty( $d['images'] ) ) {
			$this->sideload_images( $post_id, $d['images'] );
		}
		if ( ! $existing ) {
			update_post_meta( $post_id, '_wbdres_import_batch', $batch );
		}
		Registrar::ensure_meta( $post_id );
		return $existing ? 'updated' : 'created';
	}

	/**
	 * Download images from URLs (http/https only, max 10).
	 *
	 * @param int    $post_id Property.
	 * @param string $urls    Pipe-separated URLs.
	 */
	private function sideload_images( $post_id, $urls ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$ids = array();
		foreach ( array_slice( array_filter( array_map( 'trim', explode( '|', $urls ) ) ), 0, 10 ) as $url ) {
			$url = esc_url_raw( $url, array( 'http', 'https' ) );
			if ( ! $url || ! wp_http_validate_url( $url ) ) {
				continue;
			}
			$id = media_sideload_image( $url, $post_id, get_the_title( $post_id ), 'id' );
			if ( ! is_wp_error( $id ) ) {
				$ids[] = (int) $id;
			}
		}
		if ( $ids ) {
			set_post_thumbnail( $post_id, $ids[0] );
			update_post_meta( $post_id, '_wbdres_gallery', array_slice( $ids, 1 ) );
		}
	}

	/**
	 * Export properties CSV (same columns as import).
	 */
	public function handle_export() {
		$this->guard( 'wbdres_tool_export' );
		$cols = self::columns();
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=properties-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fputcsv( $out, array_keys( $cols ), ',', '"', '\\' );
		$page = 1;
		do {
			$q = new \WP_Query(
				array(
					'post_type'      => Registrar::PROPERTY,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
					'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Admin-only, bounded.
					'paged'          => $page,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);
			foreach ( $q->posts as $post ) {
				$row = array();
				foreach ( $cols as $col => $target ) {
					if ( 0 === strpos( $target, 'post_' ) ) {
						$row[] = $post->$target;
					} elseif ( 0 === strpos( $target, 'tax:' ) ) {
						$terms = get_the_terms( $post, substr( $target, 4 ) );
						$row[] = $terms && ! is_wp_error( $terms ) ? implode( '|', wp_list_pluck( $terms, 'name' ) ) : '';
					} elseif ( 'agent' === $target ) {
						$aid   = (int) get_post_meta( $post->ID, '_wbdres_agent_id', true );
						$row[] = $aid ? get_the_title( $aid ) : '';
					} elseif ( 'images' === $target ) {
						$ids   = array_merge( array( (int) get_post_thumbnail_id( $post ) ), (array) get_post_meta( $post->ID, '_wbdres_gallery', true ) );
						$row[] = implode( '|', array_filter( array_map( 'wp_get_attachment_url', array_filter( array_map( 'intval', $ids ) ) ) ) );
					} else {
						$value = get_post_meta( $post->ID, $target, true );
						$row[] = is_bool( $value ) ? (int) $value : $value;
					}
				}
				fputcsv( $out, array_map( array( LeadsPage::class, 'csv_safe' ), $row ), ',', '"', '\\' );
			}
			++$page;
		} while ( $page <= $q->max_num_pages );
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Undo an import batch (deletes properties it created).
	 */
	public function handle_undo() {
		$this->guard( 'wbdres_tool_undo' );
		$batch = isset( $_POST['batch'] ) ? sanitize_key( wp_unslash( $_POST['batch'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in guard().
		if ( ! $batch ) {
			$this->back( 'error' );
		}
		$ids = get_posts(
			array(
				'post_type'      => Registrar::PROPERTY,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_key'       => '_wbdres_import_batch', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $batch, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
		}
		$batches = get_option( 'wbdres_import_batches', array() );
		$batches = array_values(
			array_filter(
				is_array( $batches ) ? $batches : array(),
				static function ( $b ) use ( $batch ) {
					return $b['id'] !== $batch;
				}
			)
		);
		update_option( 'wbdres_import_batches', $batches, false );
		delete_transient( 'wbdres_dashboard_stats' );
		$this->back( 'undone', array( 'count' => count( $ids ) ) );
	}

	/**
	 * Install sample agents, properties and leads (no remote images).
	 */
	public function handle_sample() {
		$this->guard( 'wbdres_tool_sample' );
		Registrar::insert_default_terms();
		SampleData::install();
		delete_transient( 'wbdres_dashboard_stats' );
		$this->back( 'sample' );
	}

	/**
	 * Remove sample content.
	 */
	public function handle_sample_remove() {
		$this->guard( 'wbdres_tool_sample_remove' );
		SampleData::remove();
		delete_transient( 'wbdres_dashboard_stats' );
		$this->back( 'sample_removed' );
	}

	/**
	 * Create demo pages using the bundled patterns.
	 */
	public function handle_pages() {
		$this->guard( 'wbdres_tool_pages' );
		self::create_demo_pages();
		$this->back( 'pages' );
	}

	/**
	 * Create the demo pages from bundled patterns (skips pages that already exist).
	 *
	 * @return array key => page ID.
	 */
	public static function create_demo_pages() {
		$pages    = array(
			'home'      => array( __( 'Real Estate Home', 'wbd-real-estate-suite' ), 'wbd-real-estate-suite/homepage' ),
			'search'    => array( __( 'Find a Property', 'wbd-real-estate-suite' ), 'wbd-real-estate-suite/search-listing' ),
			'agents'    => array( __( 'Our Agents', 'wbd-real-estate-suite' ), 'wbd-real-estate-suite/agent-directory' ),
			'submit'    => array( __( 'List Your Property', 'wbd-real-estate-suite' ), 'wbd-real-estate-suite/submit-property' ),
			'favorites' => array( __( 'Saved Properties', 'wbd-real-estate-suite' ), 'wbd-real-estate-suite/saved-properties' ),
			'compare'   => array( __( 'Compare Properties', 'wbd-real-estate-suite' ), 'wbd-real-estate-suite/compare' ),
			'contact'   => array( __( 'Contact Us', 'wbd-real-estate-suite' ), 'wbd-real-estate-suite/contact' ),
		);
		$created  = get_option( 'wbdres_demo_pages', array() );
		$created  = is_array( $created ) ? $created : array();
		$registry = \WP_Block_Patterns_Registry::get_instance();
		$no_title = wp_is_block_theme() && function_exists( 'get_block_template' ) && get_block_template( get_stylesheet() . '//page-no-title' );
		foreach ( $pages as $key => $def ) {
			if ( ! empty( $created[ $key ] ) && get_post( $created[ $key ] ) ) {
				continue;
			}
			$pattern = $registry->get_registered( $def[1] );
			if ( ! $pattern ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $def[0],
					'post_content' => $pattern['content'],
				)
			);
			if ( $id && ! is_wp_error( $id ) ) {
				$created[ $key ] = (int) $id;
				// Patterns include their own headings: use the theme's title-less page template when available.
				if ( $no_title ) {
					update_post_meta( $id, '_wp_page_template', 'page-no-title' );
				}
			}
		}
		update_option( 'wbdres_demo_pages', $created, false );
		return $created;
	}

	/**
	 * Render.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_wbdres' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'wbd-real-estate-suite' ) );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display only.
		$msg   = isset( $_GET['wbdres_msg'] ) ? sanitize_key( wp_unslash( $_GET['wbdres_msg'] ) ) : '';
		$count = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;
		// phpcs:enable
		$notices = array(
			'nofile'         => array( 'error', __( 'Please choose a CSV file.', 'wbd-real-estate-suite' ) ),
			'badfile'        => array( 'error', __( 'The file must be a CSV under 10 MB.', 'wbd-real-estate-suite' ) ),
			'notitle'        => array( 'error', __( 'The CSV needs a "title" column.', 'wbd-real-estate-suite' ) ),
			/* translators: %d: number. */
			'undone'         => array( 'success', sprintf( _n( '%d imported property deleted.', '%d imported properties deleted.', $count, 'wbd-real-estate-suite' ), $count ) ),
			'sample'         => array( 'success', __( 'Sample agents, properties and leads were added.', 'wbd-real-estate-suite' ) ),
			'sample_removed' => array( 'success', __( 'Sample content removed.', 'wbd-real-estate-suite' ) ),
			'pages'          => array( 'success', __( 'Demo pages were created. Find them under Pages.', 'wbd-real-estate-suite' ) ),
			'error'          => array( 'error', __( 'Something went wrong.', 'wbd-real-estate-suite' ) ),
		);
		$report  = get_transient( 'wbdres_import_report_' . get_current_user_id() );
		$batches = get_option( 'wbdres_import_batches', array() );
		$demo    = get_option( 'wbdres_demo_pages', array() );
		?>
		<div class="wrap wbdres-admin">
			<div class="wbdres-admin__head"><div>
				<h1 class="wbdres-admin__title"><?php esc_html_e( 'Import / Export & Tools', 'wbd-real-estate-suite' ); ?></h1>
				<p class="wbdres-admin__sub"><?php esc_html_e( 'Move listings in and out with CSV, set up demo content and check your installation.', 'wbd-real-estate-suite' ); ?></p>
			</div></div>
			<hr class="wp-header-end" />
			<?php
			if ( isset( $notices[ $msg ] ) ) {
				echo '<div class="notice notice-' . esc_attr( $notices[ $msg ][0] ) . ' is-dismissible"><p>' . esc_html( $notices[ $msg ][1] ) . '</p></div>';
			}
			if ( 'imported' === $msg && is_array( $report ) ) {
				$this->render_report( $report );
			}
			?>
			<div class="wbdres-dash-grid wbdres-dash-grid--2">
				<section class="wbdres-card-panel">
					<h2><?php esc_html_e( 'Import properties from CSV', 'wbd-real-estate-suite' ); ?></h2>
					<p class="wbdres-muted"><?php esc_html_e( 'Columns are matched by header name. Only "title" is required. Separate multiple terms or image URLs with |.', 'wbd-real-estate-suite' ); ?></p>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wbdres_tool_import" />
						<?php wp_nonce_field( 'wbdres_tool_import' ); ?>
						<p><label for="wbdres-csv"><strong><?php esc_html_e( 'CSV file', 'wbd-real-estate-suite' ); ?></strong></label><br /><input type="file" name="csv" id="wbdres-csv" accept=".csv,text/csv" required /></p>
						<fieldset><legend class="screen-reader-text"><?php esc_html_e( 'Import options', 'wbd-real-estate-suite' ); ?></legend>
							<p><label><input type="checkbox" name="dry_run" value="1" checked /> <?php esc_html_e( 'Dry run — validate and preview without saving', 'wbd-real-estate-suite' ); ?></label></p>
							<p><label for="wbdres-dupes"><?php esc_html_e( 'When the "reference" already exists:', 'wbd-real-estate-suite' ); ?></label> <select name="duplicates" id="wbdres-dupes"><option value="skip"><?php esc_html_e( 'Skip the row', 'wbd-real-estate-suite' ); ?></option><option value="update"><?php esc_html_e( 'Update the existing property', 'wbd-real-estate-suite' ); ?></option></select></p>
							<p><label for="wbdres-defstatus"><?php esc_html_e( 'Save new properties as:', 'wbd-real-estate-suite' ); ?></label> <select name="default_status" id="wbdres-defstatus"><option value="draft"><?php esc_html_e( 'Draft', 'wbd-real-estate-suite' ); ?></option><option value="publish"><?php esc_html_e( 'Published', 'wbd-real-estate-suite' ); ?></option></select></p>
							<p><label><input type="checkbox" name="download_images" value="1" /> <?php esc_html_e( 'Download images from the "images" column into the Media Library', 'wbd-real-estate-suite' ); ?></label></p>
						</fieldset>
						<p><button type="submit" class="button button-primary wbdres-btn"><?php esc_html_e( 'Upload & process', 'wbd-real-estate-suite' ); ?></button></p>
					</form>
					<details><summary><?php esc_html_e( 'Supported columns', 'wbd-real-estate-suite' ); ?></summary><p><code><?php echo esc_html( implode( ', ', array_keys( self::columns() ) ) ); ?></code></p></details>
					<?php if ( $batches ) : ?>
						<h3><?php esc_html_e( 'Recent imports', 'wbd-real-estate-suite' ); ?></h3>
						<table class="widefat striped"><thead><tr><th scope="col"><?php esc_html_e( 'File', 'wbd-real-estate-suite' ); ?></th><th scope="col"><?php esc_html_e( 'Date', 'wbd-real-estate-suite' ); ?></th><th scope="col"><?php esc_html_e( 'Created', 'wbd-real-estate-suite' ); ?></th><th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'wbd-real-estate-suite' ); ?></span></th></tr></thead><tbody>
						<?php foreach ( $batches as $b ) : ?>
							<tr><td><?php echo esc_html( $b['file'] ); ?></td><td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $b['date'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( (int) $b['count'] ) ); ?></td><td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wbdres-confirm-form" data-confirm-key="confirmUndo">
									<input type="hidden" name="action" value="wbdres_tool_undo" /><input type="hidden" name="batch" value="<?php echo esc_attr( $b['id'] ); ?>" />
									<?php wp_nonce_field( 'wbdres_tool_undo' ); ?>
									<button type="submit" class="button-link wbdres-link-danger"><?php esc_html_e( 'Undo import', 'wbd-real-estate-suite' ); ?></button>
								</form></td></tr>
						<?php endforeach; ?>
						</tbody></table>
					<?php endif; ?>
				</section>

				<section class="wbdres-card-panel">
					<h2><?php esc_html_e( 'Export', 'wbd-real-estate-suite' ); ?></h2>
					<p class="wbdres-muted"><?php esc_html_e( 'Download every property (including drafts) in the same format the importer uses — handy for backups and migrations.', 'wbd-real-estate-suite' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wbdres_tool_export" />
						<?php wp_nonce_field( 'wbdres_tool_export' ); ?>
						<p><button type="submit" class="button wbdres-btn"><?php esc_html_e( 'Export properties (CSV)', 'wbd-real-estate-suite' ); ?></button>
						<a class="button wbdres-btn" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wbdres-leads&wbdres_export=1' ), 'wbdres_export_leads' ) ); ?>"><?php esc_html_e( 'Export leads (CSV)', 'wbd-real-estate-suite' ); ?></a></p>
					</form>

					<h2 id="demo"><?php esc_html_e( 'Demo content', 'wbd-real-estate-suite' ); ?></h2>
					<p class="wbdres-muted"><?php esc_html_e( 'Add sample agents, properties and leads to explore the plugin, then remove them with one click. Sample properties have no photos — add your own.', 'wbd-real-estate-suite' ); ?></p>
					<div class="wbdres-inline-forms">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="wbdres_tool_sample" /><?php wp_nonce_field( 'wbdres_tool_sample' ); ?><button type="submit" class="button wbdres-btn"><?php esc_html_e( 'Install sample content', 'wbd-real-estate-suite' ); ?></button></form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wbdres-confirm-form" data-confirm-key="confirmSample"><input type="hidden" name="action" value="wbdres_tool_sample_remove" /><?php wp_nonce_field( 'wbdres_tool_sample_remove' ); ?><button type="submit" class="button wbdres-btn"><?php esc_html_e( 'Remove sample content', 'wbd-real-estate-suite' ); ?></button></form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="wbdres_tool_pages" /><?php wp_nonce_field( 'wbdres_tool_pages' ); ?><button type="submit" class="button button-primary wbdres-btn"><?php esc_html_e( 'Create demo pages', 'wbd-real-estate-suite' ); ?></button></form>
					</div>
					<?php if ( is_array( $demo ) && $demo ) : ?>
						<ul class="wbdres-list-plain">
							<?php
							foreach ( $demo as $id ) {
								if ( get_post( $id ) ) {
									echo '<li><a href="' . esc_url( get_permalink( $id ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $id ) ) . '</a> · <a href="' . esc_url( (string) get_edit_post_link( $id ) ) . '">' . esc_html__( 'Edit', 'wbd-real-estate-suite' ) . '</a></li>';
								}
							}
							?>
						</ul>
					<?php endif; ?>

					<h2><?php esc_html_e( 'System status', 'wbd-real-estate-suite' ); ?></h2>
					<?php $this->status(); ?>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Import report.
	 *
	 * @param array $r Report.
	 */
	private function render_report( array $r ) {
		echo '<div class="wbdres-card-panel wbdres-report"><h2>' . ( $r['dry'] ? esc_html__( 'Dry run results (nothing was saved)', 'wbd-real-estate-suite' ) : esc_html__( 'Import complete', 'wbd-real-estate-suite' ) ) . '</h2>';
		echo '<ul class="wbdres-report__stats"><li><strong>' . esc_html( number_format_i18n( $r['created'] ) ) . '</strong> ' . ( $r['dry'] ? esc_html__( 'to create', 'wbd-real-estate-suite' ) : esc_html__( 'created', 'wbd-real-estate-suite' ) ) . '</li><li><strong>' . esc_html( number_format_i18n( $r['updated'] ) ) . '</strong> ' . ( $r['dry'] ? esc_html__( 'to update', 'wbd-real-estate-suite' ) : esc_html__( 'updated', 'wbd-real-estate-suite' ) ) . '</li><li><strong>' . esc_html( number_format_i18n( $r['skipped'] ) ) . '</strong> ' . esc_html__( 'skipped', 'wbd-real-estate-suite' ) . '</li></ul>';
		echo '<p class="wbdres-muted">' . esc_html__( 'Mapped columns:', 'wbd-real-estate-suite' ) . ' <code>' . esc_html( implode( ', ', $r['mapped'] ) ) . '</code></p>';
		if ( $r['unknown'] ) {
			echo '<p class="wbdres-muted">' . esc_html__( 'Ignored columns:', 'wbd-real-estate-suite' ) . ' <code>' . esc_html( implode( ', ', $r['unknown'] ) ) . '</code></p>';
		}
		if ( $r['preview'] ) {
			echo '<table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Title', 'wbd-real-estate-suite' ) . '</th><th scope="col">' . esc_html__( 'Price', 'wbd-real-estate-suite' ) . '</th><th scope="col">' . esc_html__( 'Type', 'wbd-real-estate-suite' ) . '</th><th scope="col">' . esc_html__( 'Action', 'wbd-real-estate-suite' ) . '</th></tr></thead><tbody>';
			foreach ( $r['preview'] as $p ) {
				echo '<tr><td>' . esc_html( $p['title'] ) . '</td><td>' . esc_html( $p['price'] ) . '</td><td>' . esc_html( $p['type'] ) . '</td><td>' . esc_html( $p['action'] ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
		if ( $r['errors'] ) {
			echo '<h3>' . esc_html__( 'Problems', 'wbd-real-estate-suite' ) . '</h3><ul class="wbdres-errors">';
			foreach ( $r['errors'] as $e ) {
				echo '<li>' . esc_html( $e ) . '</li>';
			}
			echo '</ul>';
		}
		if ( $r['dry'] ) {
			echo '<p><strong>' . esc_html__( 'Looks good? Upload the same file again with "Dry run" unticked.', 'wbd-real-estate-suite' ) . '</strong></p>';
		}
		echo '</div>';
	}

	/**
	 * System status table.
	 */
	private function status() {
		global $wpdb;
		$leads_ok = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', Installer::leads_table() ) ) === Installer::leads_table(); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows     = array(
			__( 'Plugin version', 'wbd-real-estate-suite' ) => WBDRES_VERSION,
			__( 'Database version', 'wbd-real-estate-suite' ) => (string) get_option( Installer::DB_OPTION, '—' ),
			__( 'Lead tables', 'wbd-real-estate-suite' )  => $leads_ok ? __( 'OK', 'wbd-real-estate-suite' ) : __( 'Missing — deactivate and reactivate the plugin', 'wbd-real-estate-suite' ),
			__( 'Total leads', 'wbd-real-estate-suite' )  => number_format_i18n( LeadRepository::counts()['all'] ),
			__( 'WordPress', 'wbd-real-estate-suite' )    => get_bloginfo( 'version' ),
			__( 'PHP', 'wbd-real-estate-suite' )          => PHP_VERSION,
			__( 'Theme type', 'wbd-real-estate-suite' )   => wp_is_block_theme() ? __( 'Block theme', 'wbd-real-estate-suite' ) : __( 'Classic theme', 'wbd-real-estate-suite' ),
			__( 'Next cleanup', 'wbd-real-estate-suite' ) => wp_next_scheduled( 'wbdres_daily_cleanup' ) ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), wp_next_scheduled( 'wbdres_daily_cleanup' ) ) : '—',
		);
		echo '<table class="widefat striped wbdres-status-table"><tbody>';
		foreach ( $rows as $k => $v ) {
			echo '<tr><th scope="row">' . esc_html( $k ) . '</th><td>' . esc_html( $v ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
}
