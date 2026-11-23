<?php
/**
 * First-run setup wizard: region & currency, agency contact, demo content and pages.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * SetupWizard.
 */
final class SetupWizard {

	const SLUG   = 'wbdres-setup';
	const ACTION = 'wbdres_setup_save';
	const DONE   = 'wbdres_setup_done';

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
	}

	/**
	 * Wizard URL.
	 *
	 * @param string $step Step key.
	 * @return string
	 */
	public static function url( $step = 'region' ) {
		return add_query_arg(
			array(
				'page' => self::SLUG,
				'step' => $step,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Steps.
	 *
	 * @return array
	 */
	private static function steps() {
		return array(
			'region'  => __( 'Region & currency', 'wbd-real-estate-suite' ),
			'agency'  => __( 'Your agency', 'wbd-real-estate-suite' ),
			'content' => __( 'Pages & demo', 'wbd-real-estate-suite' ),
			'done'    => __( 'Ready', 'wbd-real-estate-suite' ),
		);
	}

	/**
	 * Regional presets.
	 *
	 * @return array key => [label, code, symbol, position, thousand, decimal, unit]
	 */
	public static function presets() {
		return array(
			'us' => array( __( 'United States', 'wbd-real-estate-suite' ), 'USD', '$', 'before', ',', '.', 'sqft' ),
			'bd' => array( __( 'Bangladesh', 'wbd-real-estate-suite' ), 'BDT', '৳', 'before_space', ',', '.', 'sqft' ),
			'in' => array( __( 'India', 'wbd-real-estate-suite' ), 'INR', '₹', 'before', ',', '.', 'sqft' ),
			'pk' => array( __( 'Pakistan', 'wbd-real-estate-suite' ), 'PKR', 'Rs', 'before_space', ',', '.', 'marla' ),
			'ae' => array( __( 'United Arab Emirates', 'wbd-real-estate-suite' ), 'AED', 'AED', 'before_space', ',', '.', 'sqft' ),
			'sa' => array( __( 'Saudi Arabia', 'wbd-real-estate-suite' ), 'SAR', 'SAR', 'before_space', ',', '.', 'sqm' ),
			'gb' => array( __( 'United Kingdom', 'wbd-real-estate-suite' ), 'GBP', '£', 'before', ',', '.', 'sqft' ),
			'eu' => array( __( 'Eurozone', 'wbd-real-estate-suite' ), 'EUR', '€', 'after_space', '.', ',', 'sqm' ),
			'ca' => array( __( 'Canada', 'wbd-real-estate-suite' ), 'CAD', '$', 'before', ',', '.', 'sqft' ),
			'au' => array( __( 'Australia', 'wbd-real-estate-suite' ), 'AUD', '$', 'before', ',', '.', 'sqm' ),
			'my' => array( __( 'Malaysia', 'wbd-real-estate-suite' ), 'MYR', 'RM', 'before_space', ',', '.', 'sqft' ),
			'za' => array( __( 'South Africa', 'wbd-real-estate-suite' ), 'ZAR', 'R', 'before_space', ' ', '.', 'sqm' ),
		);
	}

	/**
	 * Save a step.
	 */
	public function save() {
		if ( ! current_user_can( 'manage_wbdres' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'wbd-real-estate-suite' ), 403 );
		}
		check_admin_referer( self::ACTION );
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified above.
		$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
		$next = 'region';

		if ( 'region' === $step ) {
			$preset  = isset( $_POST['preset'] ) ? sanitize_key( wp_unslash( $_POST['preset'] ) ) : '';
			$presets = self::presets();
			$values  = array();
			if ( isset( $presets[ $preset ] ) ) {
				$p      = $presets[ $preset ];
				$values = array(
					'currency_code'     => $p[1],
					'currency_symbol'   => $p[2],
					'currency_position' => $p[3],
					'thousand_sep'      => $p[4],
					'decimal_sep'       => $p[5],
					'area_unit'         => $p[6],
				);
			}
			// Manual overrides from the "Customize" panel win over the preset.
			if ( ! empty( $_POST['customize'] ) ) {
				foreach ( array( 'currency_code', 'currency_symbol', 'area_unit' ) as $key ) {
					if ( isset( $_POST[ $key ] ) && '' !== $_POST[ $key ] ) {
						$values[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					}
				}
			}
			if ( isset( $_POST['accent_color'] ) ) {
				$values['accent_color'] = sanitize_hex_color( wp_unslash( $_POST['accent_color'] ) );
			}
			self::update_settings( $values );
			update_option( 'wbdres_setup_preset', $preset, false );
			$next = 'agency';
		} elseif ( 'agency' === $step ) {
			$agency   = isset( $_POST['agency'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['agency'] ) ), 0, 100 ) : '';
			$name     = isset( $_POST['agent_name'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['agent_name'] ) ), 0, 100 ) : '';
			$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			$phone    = isset( $_POST['phone'] ) ? mb_substr( preg_replace( '/[^0-9+()\-\s]/', '', sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ), 0, 30 ) : '';
			$whatsapp = isset( $_POST['whatsapp'] ) ? mb_substr( preg_replace( '/[^0-9+()\-\s]/', '', sanitize_text_field( wp_unslash( $_POST['whatsapp'] ) ) ), 0, 30 ) : '';
			$float    = ! empty( $_POST['whatsapp_float'] );

			$values = array(
				'whatsapp_number' => $whatsapp,
				'whatsapp_float'  => $float && $whatsapp ? 'plugin' : 'off',
			);
			if ( is_email( $email ) ) {
				$values['notify_emails'] = $email;
			}
			self::update_settings( $values );

			if ( '' !== $name ) {
				$existing = get_posts(
					array(
						'post_type'      => Registrar::AGENT,
						'title'          => $name,
						'post_status'    => 'any',
						'posts_per_page' => 1,
						'fields'         => 'ids',
					)
				);
				$agent_id = $existing ? (int) $existing[0] : wp_insert_post(
					array(
						'post_type'   => Registrar::AGENT,
						'post_status' => 'publish',
						'post_title'  => $name,
					)
				);
				if ( $agent_id && ! is_wp_error( $agent_id ) ) {
					update_post_meta( $agent_id, '_wbdres_agent_agency', $agency );
					update_post_meta( $agent_id, '_wbdres_agent_phone', $phone );
					update_post_meta( $agent_id, '_wbdres_agent_whatsapp', $whatsapp );
					if ( is_email( $email ) ) {
						update_post_meta( $agent_id, '_wbdres_agent_email', $email );
					}
					update_post_meta( $agent_id, '_wbdres_agent_user_id', get_current_user_id() );
					update_option( 'wbdres_setup_agent', (int) $agent_id, false );
				}
			}
			$next = 'content';
		} elseif ( 'content' === $step ) {
			if ( ! empty( $_POST['sample'] ) ) {
				Registrar::insert_default_terms();
				SampleData::install();
			}
			$pages = array();
			if ( ! empty( $_POST['pages'] ) ) {
				$pages = ToolsPage::create_demo_pages();
			}
			if ( ! empty( $_POST['front_page'] ) && ! empty( $pages['home'] ) ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', (int) $pages['home'] );
			}
			if ( ! empty( $_POST['menu'] ) && $pages && ! wp_is_block_theme() ) {
				self::create_menu( $pages );
			}
			delete_transient( 'wbdres_dashboard_stats' );
			update_option( self::DONE, time(), false );
			delete_option( 'wbdres_show_welcome' );
			$next = 'done';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		wp_safe_redirect( self::url( $next ) );
		exit;
	}

	/**
	 * Merge values into the settings option through the regular sanitizer.
	 *
	 * @param array $values Values.
	 */
	private static function update_settings( array $values ) {
		$current = Settings::all();
		$input   = array_merge( $current, array_filter( $values, 'is_string' ) );
		// Keep checkbox values as they are.
		$input['_present'] = array();
		update_option( Settings::OPTION, Settings::sanitize( $input ) );
		Settings::flush();
	}

	/**
	 * Classic themes: create a "Real Estate" menu and assign it to the first empty location.
	 *
	 * @param array $pages key => page ID.
	 */
	private static function create_menu( array $pages ) {
		$menu = wp_get_nav_menu_object( 'Real Estate' );
		$id   = $menu ? (int) $menu->term_id : wp_create_nav_menu( 'Real Estate' );
		if ( is_wp_error( $id ) || ! $id ) {
			return;
		}
		if ( ! $menu ) {
			$archive = get_post_type_archive_link( Registrar::PROPERTY );
			if ( $archive ) {
				wp_update_nav_menu_item(
					$id,
					0,
					array(
						'menu-item-title'  => __( 'Properties', 'wbd-real-estate-suite' ),
						'menu-item-url'    => $archive,
						'menu-item-status' => 'publish',
						'menu-item-type'   => 'custom',
					)
				);
			}
			foreach ( array( 'search', 'agents', 'submit', 'favorites', 'contact' ) as $key ) {
				if ( empty( $pages[ $key ] ) ) {
					continue;
				}
				wp_update_nav_menu_item(
					$id,
					0,
					array(
						'menu-item-object-id' => (int) $pages[ $key ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}
		}
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		$locations = is_array( $locations ) ? $locations : array();
		foreach ( array_keys( get_registered_nav_menus() ) as $location ) {
			if ( empty( $locations[ $location ] ) ) {
				$locations[ $location ] = $id;
				set_theme_mod( 'nav_menu_locations', $locations );
				break;
			}
		}
	}

	/**
	 * Render.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_wbdres' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'wbd-real-estate-suite' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Step routing only.
		$step  = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'region';
		$steps = self::steps();
		$step  = isset( $steps[ $step ] ) ? $step : 'region';
		$keys  = array_keys( $steps );
		$index = array_search( $step, $keys, true );

		echo '<div class="wrap wbdres-admin wbdres-wizard">';
		echo '<div class="wbdres-wizard__brand"><span class="wbdres-wizard__logo" aria-hidden="true">' . DashboardPage::icon( 'home' ) . '</span><div><h1 class="wbdres-admin__title">' . esc_html__( 'Set up your real estate website', 'wbd-real-estate-suite' ) . '</h1><p class="wbdres-admin__sub">' . esc_html__( 'Three quick steps. You can change everything later in Settings.', 'wbd-real-estate-suite' ) . '</p></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
		echo '<hr class="wp-header-end" />';

		echo '<ol class="wbdres-wizard__steps">';
		foreach ( $keys as $i => $key ) {
			$state = $i < $index ? 'is-done' : ( $i === $index ? 'is-current' : '' );
			echo '<li class="' . esc_attr( $state ) . '"' . ( $i === $index ? ' aria-current="step"' : '' ) . '><span class="wbdres-wizard__num">' . esc_html( number_format_i18n( $i + 1 ) ) . '</span><span>' . esc_html( $steps[ $key ] ) . '</span></li>';
		}
		echo '</ol>';

		echo '<div class="wbdres-card-panel wbdres-wizard__card">';
		switch ( $step ) {
			case 'agency':
				$this->step_agency();
				break;
			case 'content':
				$this->step_content();
				break;
			case 'done':
				$this->step_done();
				break;
			default:
				$this->step_region();
		}
		echo '</div>';
		if ( 'done' !== $step ) {
			echo '<p class="wbdres-wizard__skip"><a href="' . esc_url( admin_url( 'admin.php?page=wbdres' ) ) . '">' . esc_html__( 'Skip setup and go to the dashboard', 'wbd-real-estate-suite' ) . '</a></p>';
		}
		echo '</div>';
	}

	/**
	 * Form opening.
	 *
	 * @param string $step Step.
	 */
	private function open_form( $step ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="wbdres-wizard__form">';
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '" /><input type="hidden" name="step" value="' . esc_attr( $step ) . '" />';
		wp_nonce_field( self::ACTION );
	}

	/**
	 * Footer buttons.
	 *
	 * @param string $back Previous step key ('' = none).
	 * @param string $label Submit label.
	 */
	private function close_form( $back, $label ) {
		echo '<div class="wbdres-wizard__actions">';
		if ( $back ) {
			echo '<a class="button wbdres-btn" href="' . esc_url( self::url( $back ) ) . '">' . esc_html__( '← Back', 'wbd-real-estate-suite' ) . '</a>';
		} else {
			echo '<span></span>';
		}
		echo '<button type="submit" class="button button-primary wbdres-btn">' . esc_html( $label ) . '</button></div></form>';
	}

	/**
	 * Step 1.
	 */
	private function step_region() {
		$saved = (string) get_option( 'wbdres_setup_preset', '' );
		if ( '' === $saved ) {
			$saved = 'bn_BD' === get_locale() ? 'bd' : 'us';
		}
		$this->open_form( 'region' );
		echo '<h2>' . esc_html__( 'Where do you sell or rent properties?', 'wbd-real-estate-suite' ) . '</h2><p class="wbdres-muted">' . esc_html__( 'We set the currency, number format and area unit for you.', 'wbd-real-estate-suite' ) . '</p>';
		echo '<div class="wbdres-wizard__presets" role="radiogroup" aria-label="' . esc_attr__( 'Region', 'wbd-real-estate-suite' ) . '">';
		foreach ( self::presets() as $key => $p ) {
			$units = Settings::area_units();
			echo '<label class="wbdres-wizard__preset"><input type="radio" name="preset" value="' . esc_attr( $key ) . '"' . checked( $saved, $key, false ) . ' /><span><strong>' . esc_html( $p[0] ) . '</strong><small>' . esc_html( $p[2] . ' ' . $p[1] . ' · ' . ( $units[ $p[6] ] ?? $p[6] ) ) . '</small></span></label>';
		}
		echo '</div>';

		echo '<details class="wbdres-wizard__more"><summary>' . esc_html__( 'Customize currency or area unit', 'wbd-real-estate-suite' ) . '</summary><input type="hidden" name="customize" value="1" /><div class="wbdres-wizard__grid">';
		echo '<p><label for="wbdres-w-code">' . esc_html__( 'Currency code', 'wbd-real-estate-suite' ) . '</label><input id="wbdres-w-code" name="currency_code" type="text" maxlength="3" placeholder="' . esc_attr( (string) Settings::get( 'currency_code' ) ) . '" /></p>';
		echo '<p><label for="wbdres-w-symbol">' . esc_html__( 'Currency symbol', 'wbd-real-estate-suite' ) . '</label><input id="wbdres-w-symbol" name="currency_symbol" type="text" maxlength="6" placeholder="' . esc_attr( (string) Settings::get( 'currency_symbol' ) ) . '" /></p>';
		echo '<p><label for="wbdres-w-unit">' . esc_html__( 'Area unit', 'wbd-real-estate-suite' ) . '</label><select id="wbdres-w-unit" name="area_unit"><option value="">' . esc_html__( '— From region —', 'wbd-real-estate-suite' ) . '</option>';
		foreach ( Settings::area_units() as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select></p></div></details>';

		echo '<p class="wbdres-wizard__color"><label for="wbdres-w-accent">' . esc_html__( 'Brand colour', 'wbd-real-estate-suite' ) . '</label><input id="wbdres-w-accent" name="accent_color" type="color" value="' . esc_attr( (string) Settings::get( 'accent_color', '#0f766e' ) ) . '" /><span class="wbdres-muted">' . esc_html__( 'Used for buttons, prices and highlights.', 'wbd-real-estate-suite' ) . '</span></p>';
		$this->close_form( '', __( 'Continue →', 'wbd-real-estate-suite' ) );
	}

	/**
	 * Step 2.
	 */
	private function step_agency() {
		$user  = wp_get_current_user();
		$agent = (int) get_option( 'wbdres_setup_agent', 0 );
		$get   = static function ( $key, $fallback = '' ) use ( $agent ) {
			$v = $agent ? (string) get_post_meta( $agent, $key, true ) : '';
			return '' !== $v ? $v : $fallback;
		};
		$name  = $agent && get_post( $agent ) ? get_the_title( $agent ) : $user->display_name;
		$this->open_form( 'agency' );
		echo '<h2>' . esc_html__( 'Who should buyers contact?', 'wbd-real-estate-suite' ) . '</h2><p class="wbdres-muted">' . esc_html__( 'We create your agent profile. Inquiries go to this email and appear in Leads.', 'wbd-real-estate-suite' ) . '</p>';
		echo '<div class="wbdres-wizard__grid">';
		echo '<p><label for="wbdres-w-agency">' . esc_html__( 'Agency / company name', 'wbd-real-estate-suite' ) . '</label><input id="wbdres-w-agency" name="agency" type="text" value="' . esc_attr( $get( '_wbdres_agent_agency', get_bloginfo( 'name' ) ) ) . '" /></p>';
		echo '<p><label for="wbdres-w-name">' . esc_html__( 'Your name (agent)', 'wbd-real-estate-suite' ) . '</label><input id="wbdres-w-name" name="agent_name" type="text" value="' . esc_attr( $name ) . '" /></p>';
		echo '<p><label for="wbdres-w-email">' . esc_html__( 'Email for new inquiries', 'wbd-real-estate-suite' ) . '</label><input id="wbdres-w-email" name="email" type="email" value="' . esc_attr( $get( '_wbdres_agent_email', (string) get_option( 'admin_email' ) ) ) . '" /></p>';
		echo '<p><label for="wbdres-w-phone">' . esc_html__( 'Phone', 'wbd-real-estate-suite' ) . '</label><input id="wbdres-w-phone" name="phone" type="tel" value="' . esc_attr( $get( '_wbdres_agent_phone' ) ) . '" placeholder="+880 1712 345678" /></p>';
		echo '<p><label for="wbdres-w-wa">' . esc_html__( 'WhatsApp number', 'wbd-real-estate-suite' ) . '</label><input id="wbdres-w-wa" name="whatsapp" type="tel" value="' . esc_attr( $get( '_wbdres_agent_whatsapp', (string) Settings::get( 'whatsapp_number' ) ) ) . '" placeholder="+8801712345678" /><small class="wbdres-muted">' . esc_html__( 'With country code. Buyers can message you with the property link pre-filled.', 'wbd-real-estate-suite' ) . '</small></p>';
		echo '<p class="wbdres-wizard__check"><label><input type="checkbox" name="whatsapp_float" value="1"' . checked( 'off' !== Settings::get( 'whatsapp_float', 'off' ) || ! get_option( self::DONE ), true, false ) . ' /> ' . esc_html__( 'Show a floating WhatsApp button on property pages', 'wbd-real-estate-suite' ) . '</label></p>';
		echo '</div>';
		$this->close_form( 'region', __( 'Continue →', 'wbd-real-estate-suite' ) );
	}

	/**
	 * Step 3.
	 */
	private function step_content() {
		$sample = (bool) get_option( 'wbdres_sample_installed' );
		$pages  = get_option( 'wbdres_demo_pages', array() );
		$this->open_form( 'content' );
		echo '<h2>' . esc_html__( 'Build your pages', 'wbd-real-estate-suite' ) . '</h2><p class="wbdres-muted">' . esc_html__( 'Start with a complete website and replace the demo listings with your own.', 'wbd-real-estate-suite' ) . '</p>';
		echo '<div class="wbdres-wizard__options">';
		self::option( 'pages', __( 'Create website pages', 'wbd-real-estate-suite' ), __( 'Home, Find a Property, Our Agents, List Your Property, Saved Properties, Compare and Contact — built from the bundled patterns.', 'wbd-real-estate-suite' ), true, ! empty( $pages ) ? __( 'Already created', 'wbd-real-estate-suite' ) : '' );
		self::option( 'sample', __( 'Add sample listings', 'wbd-real-estate-suite' ), __( '9 demo properties, 3 agents and example leads so you can see every feature. Remove them in one click from Tools.', 'wbd-real-estate-suite' ), ! $sample, $sample ? __( 'Already installed', 'wbd-real-estate-suite' ) : '' );
		self::option( 'front_page', __( 'Use the Real Estate Home page as my homepage', 'wbd-real-estate-suite' ), __( 'Changes Settings → Reading. Recommended for a new website.', 'wbd-real-estate-suite' ), 'page' !== get_option( 'show_on_front' ) );
		if ( ! wp_is_block_theme() ) {
			self::option( 'menu', __( 'Add the pages to a menu', 'wbd-real-estate-suite' ), __( 'Creates a "Real Estate" menu and assigns it to an empty menu location of your theme.', 'wbd-real-estate-suite' ), true );
		}
		echo '</div>';
		$this->close_form( 'agency', __( 'Finish setup', 'wbd-real-estate-suite' ) );
	}

	/**
	 * Option card.
	 *
	 * @param string $name    Field.
	 * @param string $label   Label.
	 * @param string $help    Help.
	 * @param bool   $checked Default.
	 * @param string $badge   Status badge.
	 */
	private static function option( $name, $label, $help, $checked, $badge = '' ) {
		echo '<label class="wbdres-wizard__option"><input type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( $checked, true, false ) . ' /><span><strong>' . esc_html( $label ) . ( $badge ? ' <em class="wbdres-badge-soft">' . esc_html( $badge ) . '</em>' : '' ) . '</strong><small>' . esc_html( $help ) . '</small></span></label>';
	}

	/**
	 * Step 4.
	 */
	private function step_done() {
		$pages = get_option( 'wbdres_demo_pages', array() );
		$home  = ! empty( $pages['home'] ) ? get_permalink( (int) $pages['home'] ) : home_url( '/' );
		echo '<div class="wbdres-wizard__done"><span class="wbdres-wizard__check-icon" aria-hidden="true">✓</span><h2>' . esc_html__( 'Your real estate website is ready!', 'wbd-real-estate-suite' ) . '</h2><p class="wbdres-muted">' . esc_html__( 'Here is what to do next:', 'wbd-real-estate-suite' ) . '</p></div>';
		$links = array(
			array( $home, __( 'View your website', 'wbd-real-estate-suite' ), __( 'See the homepage, search and listings as visitors do.', 'wbd-real-estate-suite' ), true ),
			array( admin_url( 'post-new.php?post_type=' . Registrar::PROPERTY ), __( 'Add your first property', 'wbd-real-estate-suite' ), __( 'Photos, price, rooms, location and features in the block editor.', 'wbd-real-estate-suite' ), false ),
			array( admin_url( 'admin.php?page=wbdres-leads' ), __( 'See your leads', 'wbd-real-estate-suite' ), __( 'Every inquiry and viewing request is stored here.', 'wbd-real-estate-suite' ), false ),
			array( admin_url( 'admin.php?page=wbdres-settings&tab=emails' ), __( 'Test email delivery', 'wbd-real-estate-suite' ), __( 'Send a test email. If it does not arrive, install an SMTP plugin.', 'wbd-real-estate-suite' ), false ),
		);
		echo '<div class="wbdres-wizard__next">';
		foreach ( $links as $l ) {
			echo '<a class="wbdres-wizard__next-item' . ( $l[3] ? ' is-primary' : '' ) . '" href="' . esc_url( $l[0] ) . '"><strong>' . esc_html( $l[1] ) . ' →</strong><small>' . esc_html( $l[2] ) . '</small></a>';
		}
		echo '</div>';
		if ( get_option( 'wbdres_sample_installed' ) ) {
			echo '<p class="wbdres-muted wbdres-wizard__note">' . esc_html__( 'Tip: when you are ready to go live, remove the sample listings in Real Estate → Tools.', 'wbd-real-estate-suite' ) . '</p>';
		}
	}
}
