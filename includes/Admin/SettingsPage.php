<?php
/**
 * Settings screen (Settings API) with tabs and email templates.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\Email\Mailer;
use Wbdres\RealEstate\Email\Templates;
use Wbdres\RealEstate\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * SettingsPage.
 */
final class SettingsPage {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_wbdres_test_email', array( $this, 'test_email' ) );
	}

	/**
	 * Register options.
	 */
	public function register_settings() {
		$cap = static function () {
			return 'manage_wbdres';
		};
		add_filter( 'option_page_capability_wbdres_settings_group', $cap );
		add_filter( 'option_page_capability_wbdres_emails_group', $cap );
		register_setting(
			'wbdres_settings_group',
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);
		register_setting(
			'wbdres_emails_group',
			Templates::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Templates::class, 'sanitize' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Tabs.
	 *
	 * @return array
	 */
	private function tabs() {
		return array(
			'general'    => __( 'General', 'wbd-real-estate-suite' ),
			'display'    => __( 'Display', 'wbd-real-estate-suite' ),
			'leads'      => __( 'Forms & Leads', 'wbd-real-estate-suite' ),
			'submission' => __( 'Frontend Submission', 'wbd-real-estate-suite' ),
			'emails'     => __( 'Email Templates', 'wbd-real-estate-suite' ),
			'advanced'   => __( 'Advanced', 'wbd-real-estate-suite' ),
		);
	}

	/**
	 * Send a test email.
	 */
	public function test_email() {
		if ( ! current_user_can( 'manage_wbdres' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'wbd-real-estate-suite' ), 403 );
		}
		check_admin_referer( 'wbdres_test_email' );
		$user = wp_get_current_user();
		$ok   = Mailer::send(
			'lead_admin',
			$user->user_email,
			array(
				'lead_name'      => 'Jane Example',
				'lead_email'     => 'jane@example.com',
				'lead_phone'     => '+1 555 0100',
				'lead_message'   => __( 'This is a test message from WBD Real Estate Suite.', 'wbd-real-estate-suite' ),
				'lead_type'      => __( 'property inquiry', 'wbd-real-estate-suite' ),
				'property_title' => __( 'Sample Property', 'wbd-real-estate-suite' ),
				'property_url'   => home_url( '/' ),
				'lead_admin_url' => admin_url( 'admin.php?page=wbdres-leads' ),
				'preferred_date' => '—',
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=wbdres-settings&tab=emails&wbdres_test=' . ( $ok ? 'ok' : 'fail' ) ) );
		exit;
	}

	/**
	 * Render.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_wbdres' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'wbd-real-estate-suite' ) );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only tab routing.
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		$tab  = array_key_exists( $tab, $this->tabs() ) ? $tab : 'general';
		$test = isset( $_GET['wbdres_test'] ) ? sanitize_key( wp_unslash( $_GET['wbdres_test'] ) ) : '';
		// phpcs:enable
		?>
		<div class="wrap wbdres-admin">
			<div class="wbdres-admin__head"><div>
				<h1 class="wbdres-admin__title"><?php esc_html_e( 'Settings', 'wbd-real-estate-suite' ); ?></h1>
				<p class="wbdres-admin__sub"><?php esc_html_e( 'Every feature is included for free. Configure it to fit your market.', 'wbd-real-estate-suite' ); ?></p>
			</div></div>
			<hr class="wp-header-end" />
			<?php settings_errors(); ?>
			<?php if ( 'ok' === $test ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Test email sent. Check your inbox (and spam folder).', 'wbd-real-estate-suite' ); ?></p></div>
			<?php elseif ( 'fail' === $test ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The test email could not be sent. Your server may need an SMTP plugin to deliver email reliably.', 'wbd-real-estate-suite' ); ?></p></div>
			<?php endif; ?>
			<nav class="nav-tab-wrapper wbdres-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'wbd-real-estate-suite' ); ?>">
				<?php foreach ( $this->tabs() as $key => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $key, admin_url( 'admin.php?page=wbdres-settings' ) ) ); ?>" class="nav-tab<?php echo $tab === $key ? ' nav-tab-active' : ''; ?>"<?php echo $tab === $key ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<?php if ( 'emails' === $tab ) : ?>
				<?php $this->render_emails(); ?>
			<?php else : ?>
				<form method="post" action="options.php" class="wbdres-card-panel wbdres-settings-form">
					<?php
					settings_fields( 'wbdres_settings_group' );
					$this->render_tab( $tab );
					submit_button();
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Field helpers.
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param string $help  Help.
	 */
	private function checkbox( $key, $label, $help = '' ) {
		$name = Settings::OPTION . '[' . $key . ']';
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td><input type="hidden" name="' . esc_attr( Settings::OPTION ) . '[_present][]" value="' . esc_attr( $key ) . '" /><label><input type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( 1, (int) Settings::get( $key ), false ) . ' /> ' . esc_html__( 'Enabled', 'wbd-real-estate-suite' ) . '</label>' . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ) . '</td></tr>';
	}

	/**
	 * Text/number input row.
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param string $type  Type.
	 * @param string $help  Help.
	 * @param array  $attrs Attributes.
	 */
	private function input( $key, $label, $type = 'text', $help = '', array $attrs = array() ) {
		$id   = 'wbdres-' . $key;
		$attr = '';
		foreach ( $attrs as $k => $v ) {
			$attr .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td><input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( Settings::OPTION . '[' . $key . ']' ) . '" value="' . esc_attr( (string) Settings::get( $key ) ) . '" class="' . ( 'number' === $type ? 'small-text' : 'regular-text' ) . '"' . $attr . ' />' . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $attr is built with esc_attr().
	}

	/**
	 * Select row.
	 *
	 * @param string $key     Key.
	 * @param string $label   Label.
	 * @param array  $options Options.
	 * @param string $help    Help.
	 */
	private function select( $key, $label, array $options, $help = '' ) {
		$id = 'wbdres-' . $key;
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td><select id="' . esc_attr( $id ) . '" name="' . esc_attr( Settings::OPTION . '[' . $key . ']' ) . '">';
		foreach ( $options as $value => $text ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( (string) Settings::get( $key ), (string) $value, false ) . '>' . esc_html( $text ) . '</option>';
		}
		echo '</select>' . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ) . '</td></tr>';
	}

	/**
	 * Render a settings tab.
	 *
	 * @param string $tab Tab.
	 */
	private function render_tab( $tab ) {
		echo '<table class="form-table" role="presentation">';
		switch ( $tab ) {
			case 'general':
				$this->input(
					'currency_code',
					__( 'Currency code', 'wbd-real-estate-suite' ),
					'text',
					__( 'ISO 4217 code used in structured data, e.g. USD, EUR, GBP, AED, INR, BDT, AUD.', 'wbd-real-estate-suite' ),
					array(
						'maxlength' => 3,
						'style'     => 'width:6em;text-transform:uppercase',
					)
				);
				$this->input(
					'currency_symbol',
					__( 'Currency symbol', 'wbd-real-estate-suite' ),
					'text',
					'',
					array(
						'maxlength' => 6,
						'style'     => 'width:6em',
					)
				);
				$this->select(
					'currency_position',
					__( 'Symbol position', 'wbd-real-estate-suite' ),
					array(
						'before'       => '$1,000',
						'before_space' => '$ 1,000',
						'after'        => '1,000$',
						'after_space'  => '1,000 $',
					)
				);
				$this->input(
					'thousand_sep',
					__( 'Thousand separator', 'wbd-real-estate-suite' ),
					'text',
					'',
					array(
						'maxlength' => 2,
						'style'     => 'width:4em',
					)
				);
				$this->input(
					'decimal_sep',
					__( 'Decimal separator', 'wbd-real-estate-suite' ),
					'text',
					'',
					array(
						'maxlength' => 2,
						'style'     => 'width:4em',
					)
				);
				$this->input(
					'decimals',
					__( 'Decimals', 'wbd-real-estate-suite' ),
					'number',
					'',
					array(
						'min' => 0,
						'max' => 4,
					)
				);
				$this->select( 'area_unit', __( 'Area unit', 'wbd-real-estate-suite' ), Settings::area_units() );
				$this->input( 'archive_slug', __( 'Properties archive slug', 'wbd-real-estate-suite' ), 'text', __( 'Your listings page URL, e.g. /properties/.', 'wbd-real-estate-suite' ) );
				$this->input( 'property_slug', __( 'Single property slug', 'wbd-real-estate-suite' ) );
				$this->input( 'agent_slug', __( 'Agent profile slug', 'wbd-real-estate-suite' ) );
				$this->input(
					'per_page',
					__( 'Properties per page', 'wbd-real-estate-suite' ),
					'number',
					'',
					array(
						'min' => 1,
						'max' => 100,
					)
				);
				break;
			case 'display':
				$this->input(
					'accent_color',
					__( 'Accent colour', 'wbd-real-estate-suite' ),
					'text',
					__( 'Hex colour for buttons, prices and highlights, e.g. #0f766e.', 'wbd-real-estate-suite' ),
					array(
						'pattern' => '#[0-9a-fA-F]{6}',
						'style'   => 'width:8em',
					)
				);
				$this->checkbox( 'auto_single_layout', __( 'Automatic property layout', 'wbd-real-estate-suite' ), __( 'Adds gallery, key facts, features, map, agent card and inquiry form to single property and agent pages. Disable if you design pages with blocks yourself.', 'wbd-real-estate-suite' ) );
				$this->checkbox( 'enable_favorites', __( 'Favorites', 'wbd-real-estate-suite' ), __( 'Heart button on cards. Stored in the visitor\'s browser only.', 'wbd-real-estate-suite' ) );
				$this->checkbox( 'enable_compare', __( 'Comparison', 'wbd-real-estate-suite' ), __( 'Compare up to 4 properties side by side.', 'wbd-real-estate-suite' ) );
				$this->checkbox( 'enable_map', __( 'Maps', 'wbd-real-estate-suite' ), __( 'OpenStreetMap embed for properties with coordinates. No API key needed.', 'wbd-real-estate-suite' ) );
				$this->checkbox( 'map_consent', __( 'Load maps only after consent', 'wbd-real-estate-suite' ), __( 'Recommended for GDPR: the map loads after the visitor clicks "Load map".', 'wbd-real-estate-suite' ) );
				$this->checkbox( 'enable_schema', __( 'Structured data (JSON-LD)', 'wbd-real-estate-suite' ), __( 'Outputs schema.org RealEstateListing markup on property pages.', 'wbd-real-estate-suite' ) );
				$this->checkbox( 'enable_views', __( 'Count property views', 'wbd-real-estate-suite' ), __( 'Anonymous, privacy-friendly view counter used in Analytics.', 'wbd-real-estate-suite' ) );
				$this->input(
					'mortgage_rate',
					__( 'Default interest rate (%)', 'wbd-real-estate-suite' ),
					'number',
					'',
					array(
						'min'  => 0,
						'max'  => 30,
						'step' => '0.05',
					)
				);
				$this->input(
					'mortgage_years',
					__( 'Default loan term (years)', 'wbd-real-estate-suite' ),
					'number',
					'',
					array(
						'min' => 1,
						'max' => 50,
					)
				);
				$this->input(
					'mortgage_down',
					__( 'Default down payment (%)', 'wbd-real-estate-suite' ),
					'number',
					'',
					array(
						'min' => 0,
						'max' => 100,
					)
				);
				break;
			case 'leads':
				$this->input( 'notify_emails', __( 'Notification emails', 'wbd-real-estate-suite' ), 'text', __( 'Comma-separated. Leave empty to use the site admin email.', 'wbd-real-estate-suite' ) );
				$this->checkbox( 'notify_agent', __( 'Notify the listing agent', 'wbd-real-estate-suite' ) );
				$this->checkbox( 'autoreply', __( 'Send auto-reply to visitors', 'wbd-real-estate-suite' ) );
				$this->checkbox( 'consent_required', __( 'Require privacy consent', 'wbd-real-estate-suite' ) );
				echo '<tr><th scope="row"><label for="wbdres-consent_text">' . esc_html__( 'Consent text', 'wbd-real-estate-suite' ) . '</label></th><td><textarea id="wbdres-consent_text" name="' . esc_attr( Settings::OPTION ) . '[consent_text]" rows="3" class="large-text">' . esc_textarea( (string) Settings::get( 'consent_text' ) ) . '</textarea><p class="description">' . esc_html__( 'A link to your Privacy Policy page is added automatically.', 'wbd-real-estate-suite' ) . '</p></td></tr>';
				echo '<tr><th scope="row"><label for="wbdres-privacy_page_id">' . esc_html__( 'Privacy policy page', 'wbd-real-estate-suite' ) . '</label></th><td>';
				wp_dropdown_pages(
					array(
						'name'              => esc_attr( Settings::OPTION ) . '[privacy_page_id]',
						'id'                => 'wbdres-privacy_page_id',
						'selected'          => (int) Settings::get( 'privacy_page_id' ),
						'show_option_none'  => esc_html__( '— Use WordPress privacy page —', 'wbd-real-estate-suite' ),
						'option_none_value' => 0,
					)
				);
				echo '</td></tr>';
				$this->input(
					'rate_limit',
					__( 'Max messages per visitor per hour', 'wbd-real-estate-suite' ),
					'number',
					__( 'Spam protection. Visitors are identified by a salted hash of their IP; raw IPs are never stored.', 'wbd-real-estate-suite' ),
					array(
						'min' => 1,
						'max' => 100,
					)
				);
				$this->input( 'lead_retention_days', __( 'Delete leads after (days)', 'wbd-real-estate-suite' ), 'number', __( '0 keeps leads forever. Set a retention period to comply with your data-protection policy.', 'wbd-real-estate-suite' ),
					array(
						'min' => 0,
						'max' => 3650,
					)
				);
				echo '</table><h2 class="title">' . esc_html__( 'WhatsApp', 'wbd-real-estate-suite' ) . '</h2><p>' . esc_html__( 'Visitors can start a WhatsApp chat with a pre-filled message about the property. Agents use their own WhatsApp number from their profile; this number is the fallback.', 'wbd-real-estate-suite' ) . '</p><table class="form-table" role="presentation">';
				$this->input(
					'whatsapp_number',
					__( 'Site WhatsApp number', 'wbd-real-estate-suite' ),
					'tel',
					__( 'Full international number with country code, e.g. +8801712345678. Leave empty to show WhatsApp only for agents who have a number.', 'wbd-real-estate-suite' ),
					array( 'placeholder' => '+8801712345678' )
				);
				$this->select(
					'whatsapp_float',
					__( 'Floating WhatsApp button', 'wbd-real-estate-suite' ),
					array(
						'off'    => __( 'Off', 'wbd-real-estate-suite' ),
						'plugin' => __( 'On property, agent and listing pages', 'wbd-real-estate-suite' ),
						'all'    => __( 'On every page', 'wbd-real-estate-suite' ),
					),
					__( 'A round WhatsApp button in the bottom corner. On property pages it uses the listing agent and the property message.', 'wbd-real-estate-suite' )
				);
				echo '<tr><th scope="row"><label for="wbdres-whatsapp_message">' . esc_html__( 'Property message', 'wbd-real-estate-suite' ) . '</label></th><td><textarea id="wbdres-whatsapp_message" name="' . esc_attr( Settings::OPTION ) . '[whatsapp_message]" rows="2" class="large-text" maxlength="300">' . esc_textarea( (string) Settings::get( 'whatsapp_message' ) ) . '</textarea><p class="description">' . esc_html__( 'Placeholders: {title}, {url}, {price}, {reference}.', 'wbd-real-estate-suite' ) . '</p></td></tr>';
				break;
			case 'submission':
				$this->checkbox( 'enable_submission', __( 'Frontend submission', 'wbd-real-estate-suite' ), __( 'Lets logged-in users submit properties for review with the Property Submission block.', 'wbd-real-estate-suite' ) );
				$this->select(
					'submission_role',
					__( 'Who can submit', 'wbd-real-estate-suite' ),
					array(
						'subscriber'   => __( 'Any logged-in user', 'wbd-real-estate-suite' ),
						'wbdres_agent' => __( 'Real Estate Agents and above', 'wbd-real-estate-suite' ),
					),
					__( 'Submissions from users without publishing rights are saved as Pending for your review.', 'wbd-real-estate-suite' )
				);
				$this->input(
					'submission_max_imgs',
					__( 'Max photos per submission', 'wbd-real-estate-suite' ),
					'number',
					'',
					array(
						'min' => 0,
						'max' => 20,
					)
				);
				break;
			case 'advanced':
				$this->checkbox( 'delete_on_uninstall', __( 'Delete all data on uninstall', 'wbd-real-estate-suite' ), __( 'Removes properties, agents, terms, leads, settings and custom tables when the plugin is deleted. Leave off to keep your data.', 'wbd-real-estate-suite' ) );
				break;
		}
		echo '</table>';
	}

	/**
	 * Email templates tab.
	 */
	private function render_emails() {
		?>
		<div class="wbdres-email-layout">
			<form method="post" action="options.php" class="wbdres-card-panel">
				<?php settings_fields( 'wbdres_emails_group' ); ?>
				<?php
				foreach ( Templates::definitions() as $event => $def ) :
					$tpl  = Templates::get( $event );
					$name = Templates::OPTION . '[' . $event . ']';
					?>
					<details class="wbdres-email-tpl">
						<summary><strong><?php echo esc_html( $def['label'] ); ?></strong> <span class="wbdres-muted">— <?php echo esc_html( $def['recipient'] ); ?></span> <?php echo $tpl['enabled'] ? '<span class="wbdres-status wbdres-status--won">' . esc_html__( 'On', 'wbd-real-estate-suite' ) . '</span>' : '<span class="wbdres-status wbdres-status--lost">' . esc_html__( 'Off', 'wbd-real-estate-suite' ) . '</span>'; ?></summary>
						<p><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $tpl['enabled'] ); ?> /> <?php esc_html_e( 'Send this email', 'wbd-real-estate-suite' ); ?></label></p>
						<p><label for="<?php echo esc_attr( 'wbdres-s-' . $event ); ?>"><?php esc_html_e( 'Subject', 'wbd-real-estate-suite' ); ?></label><br /><input type="text" class="large-text" id="<?php echo esc_attr( 'wbdres-s-' . $event ); ?>" name="<?php echo esc_attr( $name ); ?>[subject]" value="<?php echo esc_attr( $tpl['subject'] ); ?>" /></p>
						<p><label for="<?php echo esc_attr( 'wbdres-b-' . $event ); ?>"><?php esc_html_e( 'Message', 'wbd-real-estate-suite' ); ?></label><br /><textarea class="large-text code" rows="9" id="<?php echo esc_attr( 'wbdres-b-' . $event ); ?>" name="<?php echo esc_attr( $name ); ?>[body]"><?php echo esc_textarea( $tpl['body'] ); ?></textarea></p>
					</details>
				<?php endforeach; ?>
				<?php submit_button( __( 'Save templates', 'wbd-real-estate-suite' ) ); ?>
			</form>
			<aside class="wbdres-card-panel">
				<h2><?php esc_html_e( 'Placeholders', 'wbd-real-estate-suite' ); ?></h2>
				<p class="wbdres-muted"><?php esc_html_e( 'Use these in subjects and messages. Empty values are left blank.', 'wbd-real-estate-suite' ); ?></p>
				<ul class="wbdres-codes">
					<?php foreach ( Templates::placeholders() as $ph ) : ?>
						<li><code><?php echo esc_html( $ph ); ?></code></li>
					<?php endforeach; ?>
				</ul>
				<h2><?php esc_html_e( 'Deliverability', 'wbd-real-estate-suite' ); ?></h2>
				<p class="wbdres-muted"><?php esc_html_e( 'Emails are sent with wp_mail(), so any SMTP plugin works. Delivery results are logged on each lead\'s activity timeline (no email content is stored).', 'wbd-real-estate-suite' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wbdres_test_email" />
					<?php wp_nonce_field( 'wbdres_test_email' ); ?>
					<button type="submit" class="button wbdres-btn"><?php esc_html_e( 'Send test email to me', 'wbd-real-estate-suite' ); ?></button>
				</form>
			</aside>
		</div>
		<?php
	}
}
