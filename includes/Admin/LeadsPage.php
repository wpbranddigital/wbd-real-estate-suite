<?php
/**
 * Leads CRM screens: list, detail, manual add, actions and CSV export.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\Leads\LeadRepository;
use Wbdres\RealEstate\Leads\LeadService;
use Wbdres\RealEstate\PostTypes\Registrar;

defined( 'ABSPATH' ) || exit;

/**
 * LeadsPage.
 */
final class LeadsPage {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_filter( 'set-screen-option', array( $this, 'screen_option' ), 10, 3 );
	}

	/**
	 * Persist per-page option.
	 *
	 * @param mixed  $status Status.
	 * @param string $option Option.
	 * @param mixed  $value  Value.
	 * @return mixed
	 */
	public function screen_option( $status, $option, $value ) {
		return 'wbdres_leads_per_page' === $option ? min( 200, max( 5, absint( $value ) ) ) : $status;
	}

	/**
	 * Status badge.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public static function status_badge( $status ) {
		$labels = LeadRepository::statuses();
		return '<span class="wbdres-status wbdres-status--' . esc_attr( $status ) . '">' . esc_html( $labels[ $status ] ?? $status ) . '</span>';
	}

	/**
	 * Process POST/GET actions (all nonce + capability checked).
	 */
	public function handle_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Routing only; nonces verified per action below.
		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';
		if ( 'wbdres-leads' !== $page || ! LeadService::can_access() ) {
			return;
		}

		// Bulk actions from the list table.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified below.
		$bulk = isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		if ( ( ! $bulk || 'view' === $bulk || 'new' === $bulk ) && isset( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$bulk = sanitize_key( wp_unslash( $_REQUEST['action2'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( $bulk && ! in_array( $bulk, array( 'view', 'new' ), true ) && ! empty( $_REQUEST['lead'] ) && is_array( $_REQUEST['lead'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			check_admin_referer( 'bulk-leads' );
			$ids = array_map( 'absint', wp_unslash( $_REQUEST['lead'] ) );
			$ids = array_values(
				array_filter(
					$ids,
					static function ( $id ) {
						return LeadService::can_view( LeadRepository::find( $id ) );
					}
				)
			);
			if ( 'export' === $bulk ) {
				self::export_csv( $ids );
			}
			$done = 0;
			if ( 'delete' === $bulk && current_user_can( 'wbdres_manage_leads' ) ) {
				$done = LeadRepository::delete( $ids );
			} elseif ( 0 === strpos( $bulk, 'status_' ) ) {
				$status = substr( $bulk, 7 );
				foreach ( $ids as $id ) {
					$done += LeadService::change_status( LeadRepository::find( $id ), $status ) ? 1 : 0;
				}
			}
			wp_safe_redirect( add_query_arg( 'wbdres_msg', 'bulk-' . $done, remove_query_arg( array( 'action', 'action2', 'lead', '_wpnonce', '_wp_http_referer' ), wp_get_referer() ) ) );
			exit;
		}

		// Export all (filtered) leads.
		if ( isset( $_GET['wbdres_export'] ) ) {
			check_admin_referer( 'wbdres_export_leads' );
			self::export_csv( null );
		}

		// Single lead update.
		if ( isset( $_POST['wbdres_lead_update'] ) ) {
			$id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
			check_admin_referer( 'wbdres_lead_update_' . $id );
			$lead = LeadRepository::find( $id );
			if ( ! LeadService::can_view( $lead ) ) {
				wp_die( esc_html__( 'You are not allowed to edit this lead.', 'wbd-real-estate-suite' ), 403 );
			}
			if ( isset( $_POST['status'] ) ) {
				LeadService::change_status( $lead, sanitize_key( wp_unslash( $_POST['status'] ) ) );
			}
			if ( isset( $_POST['agent_id'] ) && current_user_can( 'wbdres_manage_leads' ) ) {
				LeadService::assign( LeadRepository::find( $id ), absint( $_POST['agent_id'] ) );
			}
			$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
			if ( '' !== trim( $note ) ) {
				LeadRepository::add_note( $id, mb_substr( $note, 0, 5000 ), 'note' );
			}
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'       => 'wbdres-leads',
						'action'     => 'view',
						'lead'       => $id,
						'wbdres_msg' => 'updated',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// Single delete.
		if ( isset( $_GET['wbdres_delete'] ) && current_user_can( 'wbdres_manage_leads' ) ) {
			$id = absint( $_GET['wbdres_delete'] );
			check_admin_referer( 'wbdres_delete_lead_' . $id );
			LeadRepository::delete( array( $id ) );
			wp_safe_redirect( admin_url( 'admin.php?page=wbdres-leads&wbdres_msg=deleted' ) );
			exit;
		}

		// Manual add.
		if ( isset( $_POST['wbdres_lead_create'] ) && current_user_can( 'wbdres_manage_leads' ) ) {
			check_admin_referer( 'wbdres_lead_create' );
			$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			if ( '' === $name || ! is_email( $email ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=wbdres-leads&action=new&wbdres_msg=invalid' ) );
				exit;
			}
			$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'new';
			$id     = LeadRepository::insert(
				array(
					'type'        => 'manual',
					'status'      => array_key_exists( $status, LeadRepository::statuses() ) ? $status : 'new',
					'name'        => mb_substr( $name, 0, 100 ),
					'email'       => $email,
					'phone'       => isset( $_POST['phone'] ) ? mb_substr( preg_replace( '/[^0-9+()\-\s]/', '', sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ), 0, 30 ) : '',
					'message'     => isset( $_POST['message'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['message'] ) ), 0, 3000 ) : '',
					'property_id' => isset( $_POST['property_id'] ) && Registrar::PROPERTY === get_post_type( absint( $_POST['property_id'] ) ) ? absint( $_POST['property_id'] ) : 0,
					'agent_id'    => isset( $_POST['agent_id'] ) && Registrar::AGENT === get_post_type( absint( $_POST['agent_id'] ) ) ? absint( $_POST['agent_id'] ) : 0,
					'user_id'     => 0,
					'consent'     => 1,
				)
			);
			LeadRepository::add_note( $id, __( 'Lead added manually.', 'wbd-real-estate-suite' ), 'system' );
			wp_safe_redirect( admin_url( 'admin.php?page=wbdres-leads&action=view&lead=' . $id . '&wbdres_msg=created' ) );
			exit;
		}
	}

	/**
	 * Stream leads as CSV (formula-injection safe).
	 *
	 * @param int[]|null $ids Specific IDs or null for all in scope.
	 */
	public static function export_csv( $ids ) {
		$rows = array();
		if ( is_array( $ids ) ) {
			foreach ( $ids as $id ) {
				$lead = LeadRepository::find( $id );
				if ( $lead ) {
					$rows[] = $lead;
				}
			}
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce checked by caller.
			$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
			$rows   = LeadRepository::query(
				array(
					'status'    => $status,
					'per_page'  => 500,
					'agent_ids' => LeadService::scope(),
				)
			)['items'];
		}
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=leads-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- UTF-8 BOM for Excel.
		fputcsv( $out, array( 'id', 'date_utc', 'type', 'status', 'name', 'email', 'phone', 'message', 'property', 'property_url', 'agent', 'preferred_date', 'preferred_time' ), ',', '"', '\\' );
		foreach ( $rows as $l ) {
			fputcsv(
				$out,
				array_map(
					array( __CLASS__, 'csv_safe' ),
					array(
						$l->id,
						$l->created_at,
						$l->type,
						$l->status,
						$l->name,
						$l->email,
						$l->phone,
						$l->message,
						$l->property_id ? get_the_title( (int) $l->property_id ) : '',
						$l->property_id ? get_permalink( (int) $l->property_id ) : '',
						$l->agent_id ? get_the_title( (int) $l->agent_id ) : '',
						$l->preferred_date,
						$l->preferred_time,
					)
				),
				',',
				'"',
				'\\'
			);
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Neutralise spreadsheet formulas.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function csv_safe( $value ) {
		$value = (string) $value;
		return ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) ? "'" . $value : $value;
	}

	/**
	 * Render router.
	 */
	public function render() {
		if ( ! LeadService::can_access() ) {
			wp_die( esc_html__( 'You are not allowed to view leads.', 'wbd-real-estate-suite' ) );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only routing.
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$msg    = isset( $_GET['wbdres_msg'] ) ? sanitize_key( wp_unslash( $_GET['wbdres_msg'] ) ) : '';
		// phpcs:enable
		echo '<div class="wrap wbdres-admin">';
		$this->notice( $msg );
		if ( 'view' === $action && isset( $_GET['lead'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->render_detail( absint( $_GET['lead'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( 'new' === $action && current_user_can( 'wbdres_manage_leads' ) ) {
			$this->render_new();
		} else {
			$this->render_list();
		}
		echo '</div>';
	}

	/**
	 * Admin notice.
	 *
	 * @param string $msg Code.
	 */
	private function notice( $msg ) {
		if ( ! $msg ) {
			return;
		}
		$map = array(
			'updated' => __( 'Lead updated.', 'wbd-real-estate-suite' ),
			'created' => __( 'Lead added.', 'wbd-real-estate-suite' ),
			'deleted' => __( 'Lead deleted.', 'wbd-real-estate-suite' ),
			'invalid' => __( 'Please enter a name and a valid email address.', 'wbd-real-estate-suite' ),
		);
		if ( 0 === strpos( $msg, 'bulk-' ) ) {
			/* translators: %d: number of leads. */
			$text = sprintf( _n( '%d lead updated.', '%d leads updated.', (int) substr( $msg, 5 ), 'wbd-real-estate-suite' ), (int) substr( $msg, 5 ) );
		} else {
			$text = $map[ $msg ] ?? '';
		}
		if ( $text ) {
			echo '<div class="notice notice-' . ( 'invalid' === $msg ? 'error' : 'success' ) . ' is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
		}
	}

	/**
	 * List screen.
	 */
	private function render_list() {
		$table = new LeadsListTable();
		$table->prepare_items();
		$export = wp_nonce_url( add_query_arg( 'wbdres_export', '1', admin_url( 'admin.php?page=wbdres-leads' ) ), 'wbdres_export_leads' );
		echo '<div class="wbdres-admin__head"><div><h1 class="wbdres-admin__title">' . esc_html__( 'Leads', 'wbd-real-estate-suite' ) . '</h1><p class="wbdres-admin__sub">' . esc_html__( 'Every inquiry, viewing request and agent contact in one pipeline.', 'wbd-real-estate-suite' ) . '</p></div><div class="wbdres-admin__actions">';
		if ( current_user_can( 'wbdres_manage_leads' ) ) {
			echo '<a class="button button-primary wbdres-btn" href="' . esc_url( admin_url( 'admin.php?page=wbdres-leads&action=new' ) ) . '">' . esc_html__( '+ Add lead', 'wbd-real-estate-suite' ) . '</a> ';
		}
		echo '<a class="button wbdres-btn" href="' . esc_url( $export ) . '">' . esc_html__( 'Export CSV', 'wbd-real-estate-suite' ) . '</a></div></div><hr class="wp-header-end" />';
		$table->views();
		echo '<form method="get" class="wbdres-leads-form"><input type="hidden" name="page" value="wbdres-leads" />';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['status'] ) ) {
			echo '<input type="hidden" name="status" value="' . esc_attr( sanitize_key( wp_unslash( $_GET['status'] ) ) ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$table->search_box( __( 'Search leads', 'wbd-real-estate-suite' ), 'wbdres-lead' );
		$table->display();
		echo '</form>';
	}

	/**
	 * Agent dropdown.
	 *
	 * @param int    $selected Selected.
	 * @param string $id       Element ID.
	 */
	private static function agent_select( $selected, $id ) {
		$agents = get_posts(
			array(
				'post_type'      => Registrar::AGENT,
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		echo '<select name="agent_id" id="' . esc_attr( $id ) . '"><option value="0">' . esc_html__( '— Unassigned —', 'wbd-real-estate-suite' ) . '</option>';
		foreach ( $agents as $a ) {
			echo '<option value="' . esc_attr( $a->ID ) . '"' . selected( (int) $selected, $a->ID, false ) . '>' . esc_html( get_the_title( $a ) ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Detail screen.
	 *
	 * @param int $id Lead.
	 */
	private function render_detail( $id ) {
		$lead = LeadRepository::find( $id );
		if ( ! LeadService::can_view( $lead ) ) {
			echo '<p>' . esc_html__( 'Lead not found or not accessible.', 'wbd-real-estate-suite' ) . '</p>';
			return;
		}
		$types = LeadRepository::types();
		$back  = admin_url( 'admin.php?page=wbdres-leads' );
		$ts    = strtotime( $lead->created_at . ' UTC' );
		?>
		<p><a href="<?php echo esc_url( $back ); ?>">&larr; <?php esc_html_e( 'All leads', 'wbd-real-estate-suite' ); ?></a></p>
		<div class="wbdres-admin__head">
			<div>
				<h1 class="wbdres-admin__title"><?php echo esc_html( $lead->name ); ?> <?php echo self::status_badge( $lead->status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
				<p class="wbdres-admin__sub"><?php echo esc_html( ( $types[ $lead->type ] ?? $lead->type ) . ' · ' . wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ) ); ?></p>
			</div>
			<div class="wbdres-admin__actions">
				<a class="button button-primary wbdres-btn" href="<?php echo esc_url( 'mailto:' . $lead->email . '?subject=' . rawurlencode( $lead->property_id ? get_the_title( (int) $lead->property_id ) : get_bloginfo( 'name' ) ) ); ?>"><?php esc_html_e( 'Reply by email', 'wbd-real-estate-suite' ); ?></a>
				<?php if ( $lead->phone ) : ?>
					<a class="button wbdres-btn" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $lead->phone ) ); ?>"><?php esc_html_e( 'Call', 'wbd-real-estate-suite' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<hr class="wp-header-end" />
		<div class="wbdres-lead-grid">
			<div>
				<section class="wbdres-card-panel">
					<h2><?php esc_html_e( 'Contact details', 'wbd-real-estate-suite' ); ?></h2>
					<dl class="wbdres-dl">
						<dt><?php esc_html_e( 'Email', 'wbd-real-estate-suite' ); ?></dt><dd><a href="<?php echo esc_url( 'mailto:' . $lead->email ); ?>"><?php echo esc_html( $lead->email ); ?></a></dd>
						<dt><?php esc_html_e( 'Phone', 'wbd-real-estate-suite' ); ?></dt><dd><?php echo $lead->phone ? esc_html( $lead->phone ) : '—'; ?></dd>
						<dt><?php esc_html_e( 'Property', 'wbd-real-estate-suite' ); ?></dt><dd>
							<?php
							if ( $lead->property_id ) {
								echo '<a href="' . esc_url( get_permalink( (int) $lead->property_id ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( (int) $lead->property_id ) ) . '</a> · ' . esc_html( DashboardPage::price( (int) $lead->property_id ) );
							} else {
								echo '—';
							}
							?>
						</dd>
						<?php if ( 'viewing' === $lead->type ) : ?>
							<dt><?php esc_html_e( 'Preferred viewing', 'wbd-real-estate-suite' ); ?></dt><dd><?php echo esc_html( ( $lead->preferred_date ? date_i18n( get_option( 'date_format' ), strtotime( $lead->preferred_date ) ) : '—' ) . ' ' . ucfirst( $lead->preferred_time ) ); ?></dd>
						<?php endif; ?>
						<dt><?php esc_html_e( 'Consent given', 'wbd-real-estate-suite' ); ?></dt><dd><?php echo $lead->consent ? esc_html__( 'Yes', 'wbd-real-estate-suite' ) : esc_html__( 'No', 'wbd-real-estate-suite' ); ?></dd>
						<?php if ( $lead->source_url ) : ?>
							<dt><?php esc_html_e( 'Source page', 'wbd-real-estate-suite' ); ?></dt><dd><a href="<?php echo esc_url( $lead->source_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $lead->source_url, PHP_URL_PATH ) ); ?></a></dd>
						<?php endif; ?>
					</dl>
					<h3><?php esc_html_e( 'Message', 'wbd-real-estate-suite' ); ?></h3>
					<div class="wbdres-message"><?php echo $lead->message ? nl2br( esc_html( $lead->message ) ) : '<span class="wbdres-muted">—</span>'; ?></div>
				</section>

				<section class="wbdres-card-panel">
					<h2><?php esc_html_e( 'Activity', 'wbd-real-estate-suite' ); ?></h2>
					<ol class="wbdres-timeline">
						<?php
						foreach ( LeadRepository::notes( $id ) as $note ) {
							$author = $note->author_id ? get_userdata( (int) $note->author_id ) : null;
							echo '<li class="wbdres-timeline__item wbdres-timeline__item--' . esc_attr( $note->type ) . '"><div class="wbdres-timeline__meta">' . esc_html( ( $author ? $author->display_name : __( 'System', 'wbd-real-estate-suite' ) ) . ' · ' . wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $note->created_at . ' UTC' ) ) ) . '</div><div>' . nl2br( esc_html( $note->note ) ) . '</div></li>';
						}
						?>
					</ol>
				</section>
			</div>
			<aside>
				<form method="post" class="wbdres-card-panel">
					<h2><?php esc_html_e( 'Update lead', 'wbd-real-estate-suite' ); ?></h2>
					<?php wp_nonce_field( 'wbdres_lead_update_' . $id ); ?>
					<input type="hidden" name="lead_id" value="<?php echo esc_attr( $id ); ?>" />
					<p><label for="wbdres-status"><strong><?php esc_html_e( 'Pipeline stage', 'wbd-real-estate-suite' ); ?></strong></label><br />
						<select name="status" id="wbdres-status" class="widefat">
							<?php foreach ( LeadRepository::statuses() as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $lead->status, $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select></p>
					<?php if ( current_user_can( 'wbdres_manage_leads' ) ) : ?>
						<p><label for="wbdres-agent"><strong><?php esc_html_e( 'Assigned agent', 'wbd-real-estate-suite' ); ?></strong></label><br /><?php self::agent_select( (int) $lead->agent_id, 'wbdres-agent' ); ?></p>
					<?php endif; ?>
					<p><label for="wbdres-note"><strong><?php esc_html_e( 'Add a note', 'wbd-real-estate-suite' ); ?></strong></label><br />
						<textarea name="note" id="wbdres-note" rows="4" class="widefat" placeholder="<?php esc_attr_e( 'Called the client, sent brochure…', 'wbd-real-estate-suite' ); ?>"></textarea></p>
					<p><button type="submit" name="wbdres_lead_update" value="1" class="button button-primary wbdres-btn"><?php esc_html_e( 'Save changes', 'wbd-real-estate-suite' ); ?></button></p>
					<?php if ( current_user_can( 'wbdres_manage_leads' ) ) : ?>
						<p class="wbdres-danger"><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wbdres_delete', $id, $back ), 'wbdres_delete_lead_' . $id ) ); ?>" class="wbdres-confirm" data-confirm="<?php esc_attr_e( 'Delete this lead and its activity permanently?', 'wbd-real-estate-suite' ); ?>"><?php esc_html_e( 'Delete lead', 'wbd-real-estate-suite' ); ?></a></p>
					<?php endif; ?>
				</form>
			</aside>
		</div>
		<?php
	}

	/**
	 * Manual add screen.
	 */
	private function render_new() {
		$properties = get_posts(
			array(
				'post_type'      => Registrar::PROPERTY,
				'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Admin-only, bounded.
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wbdres-leads' ) ); ?>">&larr; <?php esc_html_e( 'All leads', 'wbd-real-estate-suite' ); ?></a></p>
		<h1 class="wbdres-admin__title"><?php esc_html_e( 'Add lead', 'wbd-real-estate-suite' ); ?></h1>
		<p class="wbdres-admin__sub"><?php esc_html_e( 'Record a phone call, walk-in or referral so every opportunity lives in one pipeline.', 'wbd-real-estate-suite' ); ?></p>
		<hr class="wp-header-end" />
		<form method="post" class="wbdres-card-panel wbdres-form-narrow">
			<?php wp_nonce_field( 'wbdres_lead_create' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="wbdres-n-name"><?php esc_html_e( 'Name', 'wbd-real-estate-suite' ); ?> *</label></th><td><input type="text" required name="name" id="wbdres-n-name" class="regular-text" maxlength="100" /></td></tr>
				<tr><th scope="row"><label for="wbdres-n-email"><?php esc_html_e( 'Email', 'wbd-real-estate-suite' ); ?> *</label></th><td><input type="email" required name="email" id="wbdres-n-email" class="regular-text" /></td></tr>
				<tr><th scope="row"><label for="wbdres-n-phone"><?php esc_html_e( 'Phone', 'wbd-real-estate-suite' ); ?></label></th><td><input type="tel" name="phone" id="wbdres-n-phone" class="regular-text" maxlength="30" /></td></tr>
				<tr><th scope="row"><label for="wbdres-n-property"><?php esc_html_e( 'Property', 'wbd-real-estate-suite' ); ?></label></th><td><select name="property_id" id="wbdres-n-property"><option value="0">—</option>
					<?php foreach ( $properties as $p ) : ?>
						<option value="<?php echo esc_attr( $p->ID ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
				<tr><th scope="row"><label for="wbdres-n-agent"><?php esc_html_e( 'Agent', 'wbd-real-estate-suite' ); ?></label></th><td><?php self::agent_select( 0, 'wbdres-n-agent' ); ?></td></tr>
				<tr><th scope="row"><label for="wbdres-n-status"><?php esc_html_e( 'Stage', 'wbd-real-estate-suite' ); ?></label></th><td><select name="status" id="wbdres-n-status">
					<?php foreach ( LeadRepository::statuses() as $k => $label ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
				<tr><th scope="row"><label for="wbdres-n-message"><?php esc_html_e( 'Notes / message', 'wbd-real-estate-suite' ); ?></label></th><td><textarea name="message" id="wbdres-n-message" rows="5" class="large-text"></textarea></td></tr>
			</table>
			<p><button type="submit" name="wbdres_lead_create" value="1" class="button button-primary wbdres-btn"><?php esc_html_e( 'Add lead', 'wbd-real-estate-suite' ); ?></button></p>
		</form>
		<?php
	}
}
