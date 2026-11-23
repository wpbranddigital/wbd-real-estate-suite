<?php
/**
 * Non-JS form handlers (admin-post) and frontend property submission.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Frontend;

use Wbdres\RealEstate\Leads\LeadService;
use Wbdres\RealEstate\Meta\PropertyMeta;
use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Forms.
 */
final class Forms {

	const SUBMIT_ACTION = 'wbdres_submit_property';

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'admin_post_wbdres_inquiry', array( $this, 'handle_inquiry' ) );
		add_action( 'admin_post_nopriv_wbdres_inquiry', array( $this, 'handle_inquiry' ) );
		add_action( 'admin_post_' . self::SUBMIT_ACTION, array( $this, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_' . self::SUBMIT_ACTION, array( $this, 'redirect_login' ) );
	}

	/**
	 * Inquiry fallback when JavaScript is unavailable.
	 *
	 * Public lead form: nonces are intentionally not used because they break with
	 * full-page caching for visitors; abuse is limited by honeypot, time trap,
	 * validation and per-IP rate limiting in LeadService.
	 */
	public function handle_inquiry() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- See method docblock.
		$raw    = wp_unslash( $_POST );
		$result = LeadService::submit( is_array( $raw ) ? $raw : array() );
		$back   = wp_get_referer();
		$back   = $back ? $back : home_url( '/' );
		$back   = remove_query_arg( 'wbdres_sent', $back );
		wp_safe_redirect( add_query_arg( 'wbdres_sent', is_wp_error( $result ) ? 'error' : 'ok', $back ) . '#wbdres-contact' );
		exit;
	}

	/**
	 * Visitors must log in to submit.
	 */
	public function redirect_login() {
		$back = wp_get_referer();
		wp_safe_redirect( wp_login_url( $back ? $back : home_url( '/' ) ) );
		exit;
	}

	/**
	 * May the current user submit properties from the frontend?
	 *
	 * @return bool
	 */
	public static function can_submit() {
		if ( ! is_user_logged_in() || ! Settings::get( 'enable_submission', 1 ) ) {
			return false;
		}
		if ( current_user_can( 'edit_wbd_properties' ) ) {
			return true;
		}
		$role = (string) Settings::get( 'submission_role', 'subscriber' );
		if ( 'subscriber' === $role ) {
			return current_user_can( 'read' );
		}
		return false;
	}

	/**
	 * Handle the frontend submission form.
	 */
	public function handle_submission() {
		$back = wp_get_referer();
		$back = $back ? remove_query_arg( array( 'wbdres_submitted', 'wbdres_error' ), $back ) : home_url( '/' );

		if ( ! isset( $_POST['_wbdres_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wbdres_nonce'] ) ), self::SUBMIT_ACTION ) ) {
			wp_die( esc_html__( 'Your session has expired. Please reload the page and try again.', 'wbd-real-estate-suite' ), 403 );
		}
		if ( ! self::can_submit() ) {
			wp_die( esc_html__( 'You are not allowed to submit properties.', 'wbd-real-estate-suite' ), 403 );
		}

		$user_id = get_current_user_id();
		$key     = 'wbdres_sub_' . $user_id;
		$count   = (int) get_transient( $key );
		if ( $count >= 10 && ! current_user_can( 'edit_others_wbd_properties' ) ) {
			wp_safe_redirect( add_query_arg( 'wbdres_error', 'limit', $back ) );
			exit;
		}

		$title       = isset( $_POST['title'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['title'] ) ), 0, 150 ) : '';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		if ( mb_strlen( $title ) < 5 ) {
			wp_safe_redirect( add_query_arg( 'wbdres_error', 'title', $back ) );
			exit;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => Registrar::PROPERTY,
				'post_status'  => current_user_can( 'publish_wbd_properties' ) && current_user_can( 'edit_others_wbd_properties' ) ? 'publish' : 'pending',
				'post_title'   => $title,
				'post_content' => wpautop( $description ),
				'post_author'  => $user_id,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( add_query_arg( 'wbdres_error', 'save', $back ) );
			exit;
		}

		// Meta: reuse the registered sanitizers.
		$schema = PropertyMeta::schema();
		$fields = array( 'price', 'price_suffix', 'bedrooms', 'bathrooms', 'area', 'land_area', 'garages', 'year_built', 'address', 'postal_code', 'country', 'lat', 'lng', 'video_url' );
		foreach ( $fields as $field ) {
			$meta_key = '_wbdres_' . $field;
			if ( isset( $_POST[ $field ] ) && isset( $schema[ $meta_key ] ) && '' !== $_POST[ $field ] ) {
				$value = call_user_func( $schema[ $meta_key ][2], wp_unslash( $_POST[ $field ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by registered callback.
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
		update_post_meta( $post_id, '_wbdres_submitted', 1 );

		// Agent: users linked to an agent profile get it assigned automatically.
		$agents = LeadService::agent_ids_for_user( $user_id );
		if ( $agents ) {
			update_post_meta( $post_id, '_wbdres_agent_id', $agents[0] );
		}

		// Terms: only existing terms can be chosen.
		foreach ( array(
			'type'     => Registrar::TYPE,
			'status'   => Registrar::STATUS,
			'location' => Registrar::LOCATION,
		) as $field => $taxonomy ) {
			$term_id = isset( $_POST[ $field ] ) ? absint( $_POST[ $field ] ) : 0;
			if ( $term_id && term_exists( $term_id, $taxonomy ) ) {
				wp_set_object_terms( $post_id, array( $term_id ), $taxonomy );
			}
		}
		if ( ! empty( $_POST['features'] ) && is_array( $_POST['features'] ) ) {
			$features = array_filter(
				array_map( 'absint', wp_unslash( $_POST['features'] ) ),
				function ( $id ) {
					return $id && term_exists( $id, Registrar::FEATURE );
				}
			);
			wp_set_object_terms( $post_id, array_values( $features ), Registrar::FEATURE );
		}

		$this->handle_images( $post_id );
		Registrar::ensure_meta( $post_id );
		set_transient( $key, $count + 1, DAY_IN_SECONDS );

		/**
		 * Fires after a property is submitted from the frontend.
		 *
		 * @param int $post_id Property ID.
		 * @param int $user_id Submitting user.
		 */
		do_action( 'wbdres_property_submitted', $post_id, $user_id );

		wp_safe_redirect( add_query_arg( 'wbdres_submitted', '1', $back ) );
		exit;
	}

	/**
	 * Validate and attach uploaded images (JPEG/PNG/WebP, max 5 MB each).
	 *
	 * @param int $post_id Property.
	 */
	private function handle_images( $post_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle_submission().
		if ( empty( $_FILES['images'] ) || empty( $_FILES['images']['name'] ) || ! is_array( $_FILES['images']['name'] ) ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$max     = (int) Settings::get( 'submission_max_imgs', 8 );
		$allowed = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
		);
		$files   = $_FILES['images']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- Each file validated below.
		$ids     = array();
		$count   = min( $max, count( $files['name'] ) );

		for ( $i = 0; $i < $count; $i++ ) {
			if ( UPLOAD_ERR_OK !== (int) $files['error'][ $i ] || (int) $files['size'][ $i ] > 5 * MB_IN_BYTES ) {
				continue;
			}
			$check = wp_check_filetype_and_ext( $files['tmp_name'][ $i ], $files['name'][ $i ], $allowed );
			if ( empty( $check['type'] ) || ! in_array( $check['type'], $allowed, true ) ) {
				continue;
			}
			$_FILES['wbdres_single'] = array(
				'name'     => sanitize_file_name( $files['name'][ $i ] ),
				'type'     => $check['type'],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => 0,
				'size'     => (int) $files['size'][ $i ],
			);
			$id                      = media_handle_upload(
				'wbdres_single',
				$post_id,
				array(),
				array(
					'test_form' => false,
					'mimes'     => $allowed,
				)
			);
			if ( ! is_wp_error( $id ) ) {
				$ids[] = (int) $id;
				update_post_meta( $id, '_wp_attachment_image_alt', get_the_title( $post_id ) );
			}
		}
		unset( $_FILES['wbdres_single'] );
		if ( $ids ) {
			set_post_thumbnail( $post_id, $ids[0] );
			if ( count( $ids ) > 1 ) {
				update_post_meta( $post_id, '_wbdres_gallery', array_slice( $ids, 1 ) );
			}
		}
	}

	/**
	 * Render the submission form + "My submissions" dashboard.
	 *
	 * @param array $atts showMySubmissions, title.
	 * @return string
	 */
	public static function render_submission( array $atts = array() ) {
		$a = wp_parse_args(
			$atts,
			array(
				'showMySubmissions' => true,
				'title'             => __( 'Submit a property', 'wbd-real-estate-suite' ),
			)
		);
		Assets::enqueue_frontend();

		if ( ! is_user_logged_in() ) {
			$back = get_permalink();
			return '<div class="wbdres-panel wbdres-login-box"><h2>' . esc_html__( 'Sign in to list your property', 'wbd-real-estate-suite' ) . '</h2><p>' . esc_html__( 'Create a free account or sign in to submit a property for review.', 'wbd-real-estate-suite' ) . '</p><p class="wbdres-login-box__actions"><a class="wbdres-button" href="' . esc_url( wp_login_url( $back ) ) . '">' . esc_html__( 'Log in', 'wbd-real-estate-suite' ) . '</a>'
				. ( get_option( 'users_can_register' ) ? ' <a class="wbdres-button wbdres-button--outline" href="' . esc_url( wp_registration_url() ) . '">' . esc_html__( 'Register', 'wbd-real-estate-suite' ) . '</a>' : '' ) . '</p></div>';
		}
		if ( ! self::can_submit() ) {
			return '<div class="wbdres-notice">' . esc_html__( 'Property submission is not available for your account.', 'wbd-real-estate-suite' ) . '</div>';
		}

		$html = '';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only flags.
		if ( isset( $_GET['wbdres_submitted'] ) ) {
			$html .= '<div class="wbdres-notice wbdres-notice--success" role="status">' . esc_html__( 'Thank you! Your property was submitted and will be published after review.', 'wbd-real-estate-suite' ) . '</div>';
		}
		if ( isset( $_GET['wbdres_error'] ) ) {
			$err   = sanitize_key( wp_unslash( $_GET['wbdres_error'] ) );
			$msgs  = array(
				'title' => __( 'Please enter a title with at least 5 characters.', 'wbd-real-estate-suite' ),
				'limit' => __( 'You have reached the daily submission limit. Please try again tomorrow.', 'wbd-real-estate-suite' ),
				'save'  => __( 'The property could not be saved. Please try again.', 'wbd-real-estate-suite' ),
			);
			$html .= '<div class="wbdres-notice wbdres-notice--error" role="alert">' . esc_html( $msgs[ $err ] ?? $msgs['save'] ) . '</div>';
		}
		// phpcs:enable

		if ( $a['showMySubmissions'] ) {
			$html .= self::my_submissions();
		}

		$uid   = 'wbdres-sub';
		$html .= '<form class="wbdres-form wbdres-submit-form wbdres-panel" method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		if ( $a['title'] ) {
			$html .= '<h2 class="wbdres-form__title">' . esc_html( $a['title'] ) . '</h2>';
		}
		$html .= '<input type="hidden" name="action" value="' . esc_attr( self::SUBMIT_ACTION ) . '" />' . wp_nonce_field( self::SUBMIT_ACTION, '_wbdres_nonce', true, false );

		$html .= '<fieldset class="wbdres-fieldset"><legend>' . esc_html__( 'Basic information', 'wbd-real-estate-suite' ) . '</legend>';
		$html .= View::field(
			$uid,
			'title',
			__( 'Property title', 'wbd-real-estate-suite' ),
			'text',
			true,
			'',
			array(
				'maxlength' => 150,
				'minlength' => 5,
			)
		);
		$html .= '<div class="wbdres-field wbdres-field--full"><label for="' . esc_attr( $uid ) . '-description">' . esc_html__( 'Description', 'wbd-real-estate-suite' ) . '</label><textarea id="' . esc_attr( $uid ) . '-description" name="description" rows="6" maxlength="10000"></textarea></div>';
		$html .= '<div class="wbdres-form__grid">' . self::term_dropdown( $uid, 'type', Registrar::TYPE, __( 'Property type', 'wbd-real-estate-suite' ) ) . self::term_dropdown( $uid, 'status', Registrar::STATUS, __( 'Status', 'wbd-real-estate-suite' ) ) . '</div>';
		$html .= '</fieldset>';

		$html .= '<fieldset class="wbdres-fieldset"><legend>' . esc_html__( 'Price & details', 'wbd-real-estate-suite' ) . '</legend><div class="wbdres-form__grid">';
		$html .= View::field(
			$uid,
			'price',
			/* translators: %s: currency code. */
			sprintf( __( 'Price (%s)', 'wbd-real-estate-suite' ), Settings::get( 'currency_code', 'USD' ) ),
			'number',
			false,
			'',
			array(
				'min'       => 0,
				'step'      => 'any',
				'inputmode' => 'decimal',
			)
		);
		$html .= View::field( $uid, 'price_suffix', __( 'Price suffix (e.g. /month)', 'wbd-real-estate-suite' ), 'text', false, '', array( 'maxlength' => 30 ) );
		$html .= View::field(
			$uid,
			'bedrooms',
			__( 'Bedrooms', 'wbd-real-estate-suite' ),
			'number',
			false,
			'',
			array(
				'min' => 0,
				'max' => 99,
			)
		);
		$html .= View::field(
			$uid,
			'bathrooms',
			__( 'Bathrooms', 'wbd-real-estate-suite' ),
			'number',
			false,
			'',
			array(
				'min'  => 0,
				'max'  => 99,
				'step' => '0.5',
			)
		);
		$units = Settings::area_units();
		$unit  = Settings::get( 'area_unit', 'sqft' );
		$html .= View::field(
			$uid,
			'area',
			/* translators: %s: area unit. */
			sprintf( __( 'Floor area (%s)', 'wbd-real-estate-suite' ), $units[ $unit ] ?? $unit ),
			'number',
			false,
			'',
			array(
				'min'  => 0,
				'step' => 'any',
			)
		);
		$html .= View::field(
			$uid,
			'year_built',
			__( 'Year built', 'wbd-real-estate-suite' ),
			'number',
			false,
			'',
			array(
				'min' => 1000,
				'max' => 2200,
			)
		);
		$html .= '</div></fieldset>';

		$html .= '<fieldset class="wbdres-fieldset"><legend>' . esc_html__( 'Location', 'wbd-real-estate-suite' ) . '</legend><div class="wbdres-form__grid">';
		$html .= View::field(
			$uid,
			'address',
			__( 'Street address', 'wbd-real-estate-suite' ),
			'text',
			false,
			'',
			array(
				'autocomplete' => 'street-address',
				'maxlength'    => 200,
			)
		);
		$html .= self::term_dropdown( $uid, 'location', Registrar::LOCATION, __( 'City / area', 'wbd-real-estate-suite' ) );
		$html .= View::field(
			$uid,
			'postal_code',
			__( 'Postal code', 'wbd-real-estate-suite' ),
			'text',
			false,
			'',
			array(
				'autocomplete' => 'postal-code',
				'maxlength'    => 20,
			)
		);
		$html .= View::field(
			$uid,
			'country',
			__( 'Country', 'wbd-real-estate-suite' ),
			'text',
			false,
			'',
			array(
				'autocomplete' => 'country-name',
				'maxlength'    => 60,
			)
		);
		$html .= '</div></fieldset>';

		$features = Search::terms( Registrar::FEATURE );
		if ( $features ) {
			$html .= '<fieldset class="wbdres-fieldset"><legend>' . esc_html__( 'Features & amenities', 'wbd-real-estate-suite' ) . '</legend><div class="wbdres-chip-checks">';
			foreach ( $features as $t ) {
				$id    = $uid . '-feat-' . $t->term_id;
				$html .= '<input type="checkbox" id="' . esc_attr( $id ) . '" name="features[]" value="' . esc_attr( $t->term_id ) . '" /><label class="wbdres-chip" for="' . esc_attr( $id ) . '">' . esc_html( $t->name ) . '</label>';
			}
			$html .= '</div></fieldset>';
		}

		$max   = (int) Settings::get( 'submission_max_imgs', 8 );
		$html .= '<fieldset class="wbdres-fieldset"><legend>' . esc_html( $max > 0 ? __( 'Photos & video', 'wbd-real-estate-suite' ) : __( 'Video', 'wbd-real-estate-suite' ) ) . '</legend>';
		if ( $max > 0 ) {
			/* translators: %d: max number of photos. */
			$html .= '<div class="wbdres-field wbdres-field--full"><label for="' . esc_attr( $uid ) . '-images">' . esc_html( sprintf( __( 'Upload up to %d photos (JPEG, PNG or WebP, max 5 MB each). The first photo becomes the main photo.', 'wbd-real-estate-suite' ), $max ) ) . '</label><input type="file" id="' . esc_attr( $uid ) . '-images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple /></div>';
		}
		// The video field is independent of the photo limit.
		$html .= View::field( $uid, 'video_url', __( 'Video URL (YouTube or Vimeo)', 'wbd-real-estate-suite' ), 'url', false, '', array( 'placeholder' => 'https://' ) ) . '</fieldset>';

		$html .= '<button type="submit" class="wbdres-button">' . esc_html__( 'Submit for review', 'wbd-real-estate-suite' ) . '</button>';
		return $html . '</form>';
	}

	/**
	 * Taxonomy dropdown by term ID.
	 *
	 * @param string $uid      UID.
	 * @param string $name     Field.
	 * @param string $taxonomy Taxonomy.
	 * @param string $label    Label.
	 * @return string
	 */
	private static function term_dropdown( $uid, $name, $taxonomy, $label ) {
		$terms = Search::terms( $taxonomy );
		$html  = '<div class="wbdres-field"><label for="' . esc_attr( $uid . '-' . $name ) . '">' . esc_html( $label ) . '</label><select id="' . esc_attr( $uid . '-' . $name ) . '" name="' . esc_attr( $name ) . '"><option value="">' . esc_html__( '— Select —', 'wbd-real-estate-suite' ) . '</option>';
		foreach ( $terms as $t ) {
			$html .= '<option value="' . esc_attr( $t->term_id ) . '">' . esc_html( str_repeat( '— ', (int) $t->depth ) . $t->name ) . '</option>';
		}
		return $html . '</select></div>';
	}

	/**
	 * The current user's submitted properties.
	 *
	 * @return string
	 */
	private static function my_submissions() {
		$q = new \WP_Query(
			array(
				'post_type'      => Registrar::PROPERTY,
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'author'         => get_current_user_id(),
				'posts_per_page' => 20,
				'no_found_rows'  => true,
			)
		);
		if ( ! $q->have_posts() ) {
			return '';
		}
		$labels = array(
			'publish' => __( 'Published', 'wbd-real-estate-suite' ),
			'pending' => __( 'Pending review', 'wbd-real-estate-suite' ),
			'draft'   => __( 'Needs changes', 'wbd-real-estate-suite' ),
		);
		$html   = '<section class="wbdres-panel wbdres-my"><h2 class="wbdres-form__title">' . esc_html__( 'My properties', 'wbd-real-estate-suite' ) . '</h2><div class="wbdres-table-wrap"><table class="wbdres-table"><thead><tr><th scope="col">' . esc_html__( 'Property', 'wbd-real-estate-suite' ) . '</th><th scope="col">' . esc_html__( 'Status', 'wbd-real-estate-suite' ) . '</th><th scope="col">' . esc_html__( 'Submitted', 'wbd-real-estate-suite' ) . '</th></tr></thead><tbody>';
		foreach ( $q->posts as $post ) {
			$title = 'publish' === $post->post_status ? '<a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a>' : esc_html( get_the_title( $post ) );
			$html .= '<tr><td>' . $title . '</td><td><span class="wbdres-badge wbdres-badge--' . esc_attr( $post->post_status ) . '">' . esc_html( $labels[ $post->post_status ] ?? $post->post_status ) . '</span></td><td>' . esc_html( get_the_date( '', $post ) ) . '</td></tr>';
		}
		return $html . '</tbody></table></div></section>';
	}
}
