<?php
/**
 * Overview dashboard.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\Leads\LeadRepository;
use Wbdres\RealEstate\Leads\LeadService;
use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Support\Format;

defined( 'ABSPATH' ) || exit;

/**
 * DashboardPage.
 */
final class DashboardPage {

	/**
	 * Aggregate stats (cached 10 minutes).
	 *
	 * @return array
	 */
	public static function stats() {
		$stats = get_transient( 'wbdres_dashboard_stats' );
		if ( is_array( $stats ) ) {
			return $stats;
		}
		global $wpdb;
		$props  = wp_count_posts( Registrar::PROPERTY );
		$agents = wp_count_posts( Registrar::AGENT );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Aggregate, cached below.
		$views = (int) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wbdres_views' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Aggregate, cached below.
		$featured = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND pm.meta_value = '1' AND p.post_status = 'publish' AND p.post_type = %s", '_wbdres_featured', Registrar::PROPERTY ) );

		$now   = time();
		$stats = array(
			'published'  => (int) ( $props->publish ?? 0 ),
			'pending'    => (int) ( $props->pending ?? 0 ),
			'drafts'     => (int) ( $props->draft ?? 0 ),
			'featured'   => $featured,
			'agents'     => (int) ( $agents->publish ?? 0 ),
			'views'      => $views,
			'leads_30'   => LeadRepository::count_between( gmdate( 'Y-m-d H:i:s', $now - 30 * DAY_IN_SECONDS ), gmdate( 'Y-m-d H:i:s', $now + 60 ) ),
			'leads_prev' => LeadRepository::count_between( gmdate( 'Y-m-d H:i:s', $now - 60 * DAY_IN_SECONDS ), gmdate( 'Y-m-d H:i:s', $now - 30 * DAY_IN_SECONDS ) ),
		);
		set_transient( 'wbdres_dashboard_stats', $stats, 10 * MINUTE_IN_SECONDS );
		return $stats;
	}

	/**
	 * Trend label.
	 *
	 * @param int $current  Current period.
	 * @param int $previous Previous period.
	 * @return string HTML.
	 */
	public static function trend( $current, $previous ) {
		if ( ! $previous ) {
			return $current ? '<span class="wbdres-trend is-up">' . esc_html__( 'New this period', 'wbd-real-estate-suite' ) . '</span>' : '<span class="wbdres-trend">' . esc_html__( 'No activity yet', 'wbd-real-estate-suite' ) . '</span>';
		}
		$pct = round( ( $current - $previous ) / $previous * 100 );
		$cls = $pct >= 0 ? 'is-up' : 'is-down';
		/* translators: %s: percent change. */
		return '<span class="wbdres-trend ' . esc_attr( $cls ) . '">' . ( $pct >= 0 ? '↑ ' : '↓ ' ) . esc_html( sprintf( __( '%s%% vs previous 30 days', 'wbd-real-estate-suite' ), number_format_i18n( abs( $pct ) ) ) ) . '</span>';
	}

	/**
	 * Render.
	 */
	public function render() {
		$stats   = self::stats();
		$scope   = LeadService::can_access() ? LeadService::scope() : array();
		$counts  = LeadService::can_access() ? LeadRepository::counts( $scope ) : array( 'all' => 0 );
		$user    = wp_get_current_user();
		$manage  = current_user_can( 'wbdres_manage_leads' );
		$won     = (int) ( $counts['won'] ?? 0 );
		$all     = (int) $counts['all'];
		$conv    = $all ? round( $won / $all * 100 ) : 0;
		$leadurl = admin_url( 'admin.php?page=wbdres-leads' );
		?>
		<div class="wrap wbdres-admin">
			<div class="wbdres-admin__head">
				<div>
					<h1 class="wbdres-admin__title">
						<?php
						/* translators: %s: user display name. */
						echo esc_html( sprintf( __( 'Welcome back, %s', 'wbd-real-estate-suite' ), $user->display_name ) );
						?>
					</h1>
					<p class="wbdres-admin__sub"><?php esc_html_e( "Here's what is happening with your listings and leads.", 'wbd-real-estate-suite' ); ?></p>
				</div>
				<div class="wbdres-admin__actions">
					<?php if ( current_user_can( 'edit_wbd_properties' ) ) : ?>
						<a class="button button-primary wbdres-btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . Registrar::PROPERTY ) ); ?>"><?php esc_html_e( '+ Add property', 'wbd-real-estate-suite' ); ?></a>
					<?php endif; ?>
					<?php if ( $manage ) : ?>
						<a class="button wbdres-btn" href="<?php echo esc_url( add_query_arg( 'action', 'new', $leadurl ) ); ?>"><?php esc_html_e( '+ Add lead', 'wbd-real-estate-suite' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<hr class="wp-header-end" />

			<?php if ( 0 === $stats['published'] && 0 === $stats['drafts'] && current_user_can( 'manage_wbdres' ) ) : ?>
				<div class="wbdres-card-panel wbdres-setup">
					<h2><?php esc_html_e( 'Get started in 3 steps', 'wbd-real-estate-suite' ); ?></h2>
					<p><a class="button button-primary wbdres-btn" href="<?php echo esc_url( SetupWizard::url() ); ?>"><?php esc_html_e( 'Run the setup wizard', 'wbd-real-estate-suite' ); ?></a> <span class="wbdres-muted"><?php esc_html_e( 'or do it step by step:', 'wbd-real-estate-suite' ); ?></span></p>
					<ol>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wbdres-settings' ) ); ?>"><?php esc_html_e( 'Set your currency and area unit', 'wbd-real-estate-suite' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . Registrar::AGENT ) ); ?>"><?php esc_html_e( 'Add your agent profile', 'wbd-real-estate-suite' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . Registrar::PROPERTY ) ); ?>"><?php esc_html_e( 'Add your first property', 'wbd-real-estate-suite' ); ?></a> — <?php esc_html_e( 'or', 'wbd-real-estate-suite' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wbdres-tools#demo' ) ); ?>"><?php esc_html_e( 'install sample content and demo pages', 'wbd-real-estate-suite' ); ?></a></li>
					</ol>
				</div>
			<?php endif; ?>

			<div class="wbdres-kpis">
				<?php
				$kpis = array(
					array( 'purple', 'users', __( 'New leads', 'wbd-real-estate-suite' ), (int) ( $counts['new'] ?? 0 ), add_query_arg( 'status', 'new', $leadurl ), $manage ? self::trend( $stats['leads_30'], $stats['leads_prev'] ) : '', __( 'Leads with status "New" that nobody has contacted yet.', 'wbd-real-estate-suite' ) ),
					array( 'orange', 'phone', __( 'Follow-ups', 'wbd-real-estate-suite' ), (int) ( $counts['contacted'] ?? 0 ) + (int) ( $counts['qualified'] ?? 0 ), add_query_arg( 'status', 'contacted', $leadurl ), '', __( 'Leads that are Contacted or Qualified.', 'wbd-real-estate-suite' ) ),
					array( 'green', 'calendar', __( 'Viewings', 'wbd-real-estate-suite' ), (int) ( $counts['viewing'] ?? 0 ), add_query_arg( 'status', 'viewing', $leadurl ), '', __( 'Leads at the "Viewing scheduled" stage.', 'wbd-real-estate-suite' ) ),
					array( 'blue', 'trophy', __( 'Deals won', 'wbd-real-estate-suite' ), $won, add_query_arg( 'status', 'won', $leadurl ), '<span class="wbdres-trend">' . esc_html( sprintf( /* translators: %s: percent. */ __( '%s%% conversion', 'wbd-real-estate-suite' ), number_format_i18n( $conv ) ) ) . '</span>', __( 'Won leads ÷ all leads.', 'wbd-real-estate-suite' ) ),
				);
				if ( LeadService::can_access() ) {
					foreach ( $kpis as $k ) {
						self::kpi( $k[0], $k[1], $k[2], $k[3], $k[4], $k[5], $k[6] );
					}
				}
				self::kpi( 'teal', 'home', __( 'Published properties', 'wbd-real-estate-suite' ), $stats['published'], admin_url( 'edit.php?post_type=' . Registrar::PROPERTY . '&post_status=publish' ), '<span class="wbdres-trend">' . esc_html( sprintf( /* translators: %s: number. */ __( '%s featured', 'wbd-real-estate-suite' ), number_format_i18n( $stats['featured'] ) ) ) . '</span>', __( 'Properties with status Published.', 'wbd-real-estate-suite' ) );
				self::kpi( 'amber', 'clock', __( 'Pending review', 'wbd-real-estate-suite' ), $stats['pending'], admin_url( 'edit.php?post_type=' . Registrar::PROPERTY . '&post_status=pending' ), '', __( 'Frontend submissions waiting for approval.', 'wbd-real-estate-suite' ) );
				self::kpi( 'slate', 'agent', __( 'Active agents', 'wbd-real-estate-suite' ), $stats['agents'], admin_url( 'edit.php?post_type=' . Registrar::AGENT ), '', __( 'Published agent profiles.', 'wbd-real-estate-suite' ) );
				self::kpi( 'rose', 'eye', __( 'Property views', 'wbd-real-estate-suite' ), $stats['views'], admin_url( 'admin.php?page=wbdres-analytics' ), '', __( 'Unique visits per property (one per visitor every 6 hours, bots excluded).', 'wbd-real-estate-suite' ) );
				?>
			</div>

			<?php if ( LeadService::can_access() ) : ?>
			<div class="wbdres-dash-grid">
				<section class="wbdres-card-panel">
					<h2><?php esc_html_e( 'Lead funnel', 'wbd-real-estate-suite' ); ?></h2>
					<?php self::funnel( $counts ); ?>
				</section>
				<?php if ( $manage ) : // Site-wide aggregate: only for lead managers, not scoped agents. ?>
				<section class="wbdres-card-panel">
					<h2><?php esc_html_e( 'Leads by type (90 days)', 'wbd-real-estate-suite' ); ?></h2>
					<?php self::donut( LeadRepository::aggregate( 'type', gmdate( 'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS ) ), LeadRepository::types() ); ?>
				</section>
				<?php endif; ?>
				<section class="wbdres-card-panel">
					<div class="wbdres-card-panel__head"><h2><?php esc_html_e( 'Upcoming viewings', 'wbd-real-estate-suite' ); ?></h2></div>
					<?php
					$viewings = LeadRepository::upcoming_viewings( 5, $scope );
					if ( ! $viewings ) {
						echo '<p class="wbdres-muted">' . esc_html__( 'No upcoming viewings. Viewing requests from your property pages appear here.', 'wbd-real-estate-suite' ) . '</p>';
					} else {
						echo '<ul class="wbdres-list">';
						foreach ( array_slice( $viewings, 0, 5 ) as $v ) {
							$initials = \Wbdres\RealEstate\Support\Format::upper( mb_substr( $v->name, 0, 1 ) . mb_substr( (string) strstr( $v->name, ' ' ), 1, 1 ) );
							echo '<li><span class="wbdres-avatar">' . esc_html( $initials ) . '</span><span><strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $v->preferred_date ) ) ) . '</strong> · ' . esc_html( ucfirst( $v->preferred_time ) ) . '<br /><a href="' . esc_url(
								add_query_arg(
									array(
										'action' => 'view',
										'lead'   => (int) $v->id,
									),
									$leadurl
								)
							) . '">' . esc_html( $v->name ) . '</a> — ' . esc_html( $v->property_id ? get_the_title( (int) $v->property_id ) : '' ) . '</span></li>';
						}
						echo '</ul>';
					}
					?>
				</section>
			</div>

			<div class="wbdres-dash-grid wbdres-dash-grid--2">
				<section class="wbdres-card-panel">
					<div class="wbdres-card-panel__head"><h2><?php esc_html_e( 'Recent inquiries', 'wbd-real-estate-suite' ); ?></h2><a href="<?php echo esc_url( $leadurl ); ?>"><?php esc_html_e( 'View all', 'wbd-real-estate-suite' ); ?></a></div>
					<?php
					$recent = LeadRepository::query(
						array(
							'per_page'  => 6,
							'agent_ids' => $scope,
						)
					);
					if ( ! $recent['items'] ) {
						echo '<p class="wbdres-muted">' . esc_html__( 'No inquiries yet. Add an Inquiry Form block to your pages, or use the automatic property layout.', 'wbd-real-estate-suite' ) . '</p>';
					} else {
						echo '<table class="wbdres-mini-table"><tbody>';
						foreach ( $recent['items'] as $l ) {
							echo '<tr><td><a href="' . esc_url(
								add_query_arg(
									array(
										'action' => 'view',
										'lead'   => (int) $l->id,
									),
									$leadurl
								)
							) . '"><strong>' . esc_html( $l->name ) . '</strong></a><br /><span class="wbdres-muted">' . esc_html( $l->property_id ? get_the_title( (int) $l->property_id ) : ( LeadRepository::types()[ $l->type ] ?? '' ) ) . '</span></td><td>' . LeadsPage::status_badge( $l->status ) . '</td><td class="wbdres-muted">' . esc_html( human_time_diff( strtotime( $l->created_at . ' UTC' ) ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Badge is escaped.
						}
						echo '</tbody></table>';
					}
					?>
				</section>
				<section class="wbdres-card-panel">
					<div class="wbdres-card-panel__head"><h2><?php esc_html_e( 'Most viewed properties', 'wbd-real-estate-suite' ); ?></h2></div>
					<?php AnalyticsPage::top_viewed( 6 ); ?>
				</section>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * KPI card.
	 *
	 * @param string $tone  Colour tone.
	 * @param string $icon  Icon key.
	 * @param string $label Label.
	 * @param int    $value Value.
	 * @param string $url   Link.
	 * @param string $foot  Footer HTML (escaped).
	 * @param string $help  Definition tooltip.
	 */
	public static function kpi( $tone, $icon, $label, $value, $url, $foot = '', $help = '' ) {
		echo '<a class="wbdres-kpi wbdres-kpi--' . esc_attr( $tone ) . '" href="' . esc_url( $url ) . '" title="' . esc_attr( $help ) . '">';
		echo '<span class="wbdres-kpi__label">' . esc_html( $label ) . '</span>';
		echo '<span class="wbdres-kpi__icon" aria-hidden="true">' . self::icon( $icon ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
		echo '<strong class="wbdres-kpi__value">' . esc_html( number_format_i18n( (int) $value ) ) . '</strong>';
		echo $foot ? wp_kses_post( $foot ) : '<span class="wbdres-trend">&nbsp;</span>';
		echo '</a>';
	}

	/**
	 * Static admin icons.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	public static function icon( $name ) {
		$paths = array(
			'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.3-5.5 6.5-5.5s5.9 1.9 6.5 5.5M16 4.5a3.5 3.5 0 0 1 0 7M18 14.8c2 .7 3.2 2.4 3.5 5.2"/>',
			'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
			'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
			'trophy'   => '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4ZM7 6H4a3 3 0 0 0 3 4M17 6h3a3 3 0 0 1-3 4"/>',
			'home'     => '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z"/>',
			'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'agent'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.8-4.2 4-6.5 8-6.5s7.2 2.3 8 6.5"/>',
			'eye'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		);
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . ( $paths[ $name ] ?? '' ) . '</svg>';
	}

	/**
	 * Funnel bars by status.
	 *
	 * @param array $counts Counts.
	 */
	public static function funnel( array $counts ) {
		$statuses = LeadRepository::statuses();
		unset( $statuses['lost'] );
		echo '<ul class="wbdres-funnel">';
		$i = 0;
		foreach ( $statuses as $key => $label ) {
			$c     = (int) ( $counts[ $key ] ?? 0 );
			$width = max( 18, 100 - $i * 12 );
			echo '<li><span class="wbdres-funnel__bar wbdres-funnel__bar--' . esc_attr( $i ) . '" style="width:' . esc_attr( $width ) . '%"><span>' . esc_html( $label ) . '</span></span><strong>' . esc_html( number_format_i18n( $c ) ) . '</strong></li>';
			++$i;
		}
		echo '</ul><p class="wbdres-muted">' . esc_html( sprintf( /* translators: %s: number. */ __( 'Lost: %s', 'wbd-real-estate-suite' ), number_format_i18n( (int) ( $counts['lost'] ?? 0 ) ) ) ) . '</p>';
	}

	/**
	 * CSS conic-gradient donut chart with accessible legend.
	 *
	 * @param array $data   key => count.
	 * @param array $labels key => label.
	 */
	public static function donut( array $data, array $labels ) {
		$total = array_sum( $data );
		if ( ! $total ) {
			echo '<p class="wbdres-muted">' . esc_html__( 'No leads in this period yet.', 'wbd-real-estate-suite' ) . '</p>';
			return;
		}
		$colors = array( '#4f46e5', '#14b8a6', '#f59e0b', '#ec4899', '#64748b' );
		$stops  = array();
		$acc    = 0;
		$i      = 0;
		$legend = '';
		foreach ( $labels as $key => $label ) {
			$c = (int) ( $data[ $key ] ?? 0 );
			if ( ! $c ) {
				continue;
			}
			$color   = $colors[ $i % count( $colors ) ];
			$start   = $acc / $total * 100;
			$acc    += $c;
			$end     = $acc / $total * 100;
			$stops[] = $color . ' ' . round( $start, 2 ) . '% ' . round( $end, 2 ) . '%';
			$legend .= '<li><span class="wbdres-dot" style="background:' . esc_attr( $color ) . '"></span>' . esc_html( $label ) . '<strong>' . esc_html( number_format_i18n( $c ) ) . ' <span class="wbdres-muted">(' . esc_html( round( $c / $total * 100 ) ) . '%)</span></strong></li>';
			++$i;
		}
		echo '<div class="wbdres-donut-wrap"><div class="wbdres-donut" role="img" aria-label="' . esc_attr( sprintf( /* translators: %s: total. */ __( 'Donut chart, %s leads in total', 'wbd-real-estate-suite' ), number_format_i18n( $total ) ) ) . '" style="background:conic-gradient(' . esc_attr( implode( ',', $stops ) ) . ')"><span><strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>' . esc_html__( 'Total', 'wbd-real-estate-suite' ) . '</span></div><ul class="wbdres-legend">' . $legend . '</ul></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Legend escaped above.
	}

	/**
	 * Price helper for admin tables.
	 *
	 * @param int $post_id Property.
	 * @return string
	 */
	public static function price( $post_id ) {
		return Format::price( (float) get_post_meta( $post_id, '_wbdres_price', true ), (string) get_post_meta( $post_id, '_wbdres_price_suffix', true ), (bool) get_post_meta( $post_id, '_wbdres_price_on_request', true ) );
	}
}
