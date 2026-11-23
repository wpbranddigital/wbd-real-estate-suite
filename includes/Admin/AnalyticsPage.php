<?php
/**
 * Analytics & reports (no external services, no tracking scripts).
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\Leads\LeadRepository;
use Wbdres\RealEstate\PostTypes\Registrar;

defined( 'ABSPATH' ) || exit;

/**
 * AnalyticsPage.
 */
final class AnalyticsPage {

	/**
	 * Most viewed properties table.
	 *
	 * @param int $limit Limit.
	 */
	public static function top_viewed( $limit = 10 ) {
		$q = new \WP_Query(
			array(
				'post_type'              => Registrar::PROPERTY,
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'meta_key'               => '_wbdres_views', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'                => 'meta_value_num',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);
		if ( ! $q->have_posts() ) {
			echo '<p class="wbdres-muted">' . esc_html__( 'No views recorded yet.', 'wbd-real-estate-suite' ) . '</p>';
			return;
		}
		$max = max( 1, (int) get_post_meta( $q->posts[0]->ID, '_wbdres_views', true ) );
		echo '<ul class="wbdres-bars">';
		foreach ( $q->posts as $post ) {
			$views = (int) get_post_meta( $post->ID, '_wbdres_views', true );
			echo '<li><a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a><span class="wbdres-bars__track"><span style="width:' . esc_attr( round( $views / $max * 100 ) ) . '%"></span></span><strong>' . esc_html( number_format_i18n( $views ) ) . '</strong></li>';
		}
		echo '</ul>';
	}

	/**
	 * Render.
	 */
	public function render() {
		if ( ! current_user_can( 'wbdres_manage_leads' ) ) {
			wp_die( esc_html__( 'You are not allowed to view analytics.', 'wbd-real-estate-suite' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.
		$range = isset( $_GET['range'] ) ? absint( $_GET['range'] ) : 30;
		$range = in_array( $range, array( 7, 30, 90, 365 ), true ) ? $range : 30;
		$now   = time();
		$since = gmdate( 'Y-m-d H:i:s', $now - $range * DAY_IN_SECONDS );
		$prev  = gmdate( 'Y-m-d H:i:s', $now - 2 * $range * DAY_IN_SECONDS );

		$total     = LeadRepository::count_between( $since, gmdate( 'Y-m-d H:i:s', $now + 60 ) );
		$previous  = LeadRepository::count_between( $prev, $since );
		$by_status = LeadRepository::aggregate( 'status', $since );
		$by_type   = LeadRepository::aggregate( 'type', $since );
		$series    = LeadRepository::aggregate( $range > 90 ? 'month' : 'day', $since );
		$top       = LeadRepository::aggregate( 'property', $since );
		$won       = (int) ( $by_status['won'] ?? 0 );
		$base      = admin_url( 'admin.php?page=wbdres-analytics' );
		?>
		<div class="wrap wbdres-admin">
			<div class="wbdres-admin__head">
				<div>
					<h1 class="wbdres-admin__title"><?php esc_html_e( 'Analytics', 'wbd-real-estate-suite' ); ?></h1>
					<p class="wbdres-admin__sub"><?php esc_html_e( 'Lead volume, pipeline health and listing performance. All data stays on your site.', 'wbd-real-estate-suite' ); ?></p>
				</div>
				<nav class="wbdres-segmented-admin" aria-label="<?php esc_attr_e( 'Date range', 'wbd-real-estate-suite' ); ?>">
					<?php
					foreach ( array(
						7   => __( '7 days', 'wbd-real-estate-suite' ),
						30  => __( '30 days', 'wbd-real-estate-suite' ),
						90  => __( '90 days', 'wbd-real-estate-suite' ),
						365 => __( '12 months', 'wbd-real-estate-suite' ),
					) as $days => $label ) {
						echo '<a href="' . esc_url( add_query_arg( 'range', $days, $base ) ) . '"' . ( $days === $range ? ' class="is-active" aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
					}
					?>
				</nav>
			</div>
			<hr class="wp-header-end" />

			<div class="wbdres-kpis wbdres-kpis--3">
				<?php
				DashboardPage::kpi( 'purple', 'users', __( 'Leads in period', 'wbd-real-estate-suite' ), $total, admin_url( 'admin.php?page=wbdres-leads' ), DashboardPage::trend( $total, $previous ), __( 'Leads created during the selected range.', 'wbd-real-estate-suite' ) );
				DashboardPage::kpi( 'green', 'calendar', __( 'Viewing requests', 'wbd-real-estate-suite' ), (int) ( $by_type['viewing'] ?? 0 ), admin_url( 'admin.php?page=wbdres-leads&type=viewing' ), '', __( 'Leads submitted with the Schedule a viewing form.', 'wbd-real-estate-suite' ) );
				/* translators: %s: percent. */
				DashboardPage::kpi( 'blue', 'trophy', __( 'Won in period', 'wbd-real-estate-suite' ), $won, admin_url( 'admin.php?page=wbdres-leads&status=won' ), '<span class="wbdres-trend">' . esc_html( sprintf( __( '%s%% of period leads', 'wbd-real-estate-suite' ), number_format_i18n( $total ? round( $won / $total * 100 ) : 0 ) ) ) . '</span>', __( 'Leads created in the period that are now Won.', 'wbd-real-estate-suite' ) );
				?>
			</div>

			<section class="wbdres-card-panel">
				<h2><?php esc_html_e( 'Leads over time', 'wbd-real-estate-suite' ); ?></h2>
				<?php self::columns_chart( $series, $range ); ?>
			</section>

			<div class="wbdres-dash-grid">
				<section class="wbdres-card-panel">
					<h2><?php esc_html_e( 'Pipeline by stage', 'wbd-real-estate-suite' ); ?></h2>
					<?php DashboardPage::funnel( array_merge( array( 'all' => $total ), $by_status ) ); ?>
				</section>
				<section class="wbdres-card-panel">
					<h2><?php esc_html_e( 'Lead sources', 'wbd-real-estate-suite' ); ?></h2>
					<?php DashboardPage::donut( $by_type, LeadRepository::types() ); ?>
				</section>
				<section class="wbdres-card-panel">
					<h2><?php esc_html_e( 'Properties with most leads', 'wbd-real-estate-suite' ); ?></h2>
					<?php
					if ( ! $top ) {
						echo '<p class="wbdres-muted">' . esc_html__( 'No property inquiries in this period.', 'wbd-real-estate-suite' ) . '</p>';
					} else {
						$max = max( $top );
						echo '<ul class="wbdres-bars">';
						foreach ( $top as $pid => $c ) {
							echo '<li><a href="' . esc_url( (string) get_edit_post_link( (int) $pid ) ) . '">' . esc_html( get_the_title( (int) $pid ) ) . '</a><span class="wbdres-bars__track"><span style="width:' . esc_attr( round( $c / $max * 100 ) ) . '%"></span></span><strong>' . esc_html( number_format_i18n( $c ) ) . '</strong></li>';
						}
						echo '</ul>';
					}
					?>
				</section>
			</div>
			<section class="wbdres-card-panel">
				<h2><?php esc_html_e( 'Most viewed properties (all time)', 'wbd-real-estate-suite' ); ?></h2>
				<?php self::top_viewed( 10 ); ?>
			</section>
		</div>
		<?php
	}

	/**
	 * Accessible CSS column chart with a data table fallback.
	 *
	 * @param array $series key => count.
	 * @param int   $range  Days.
	 */
	private static function columns_chart( array $series, $range ) {
		$points = array();
		if ( $range > 90 ) {
			for ( $i = 11; $i >= 0; $i-- ) {
				$key            = gmdate( 'Y-m', strtotime( "-{$i} months" ) );
				$points[ $key ] = array( date_i18n( 'M', strtotime( $key . '-01' ) ), (int) ( $series[ $key ] ?? 0 ) );
			}
		} else {
			for ( $i = $range - 1; $i >= 0; $i-- ) {
				$key            = gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS );
				$points[ $key ] = array( date_i18n( 'j M', strtotime( $key ) ), (int) ( $series[ $key ] ?? 0 ) );
			}
		}
		$max = max( 1, max( wp_list_pluck( $points, 1 ) ) );
		echo '<div class="wbdres-colchart" role="img" aria-label="' . esc_attr__( 'Column chart of leads over time. Data table follows.', 'wbd-real-estate-suite' ) . '" style="--n:' . esc_attr( count( $points ) ) . '">';
		$step = max( 1, (int) ceil( count( $points ) / 12 ) );
		$i    = 0;
		foreach ( $points as $p ) {
			echo '<div class="wbdres-colchart__col" title="' . esc_attr( $p[0] . ': ' . $p[1] ) . '"><span class="wbdres-colchart__bar" style="height:' . esc_attr( round( $p[1] / $max * 100 ) ) . '%"></span><span class="wbdres-colchart__label">' . ( 0 === $i % $step ? esc_html( $p[0] ) : '' ) . '</span></div>';
			++$i;
		}
		echo '</div><details class="wbdres-datatable"><summary>' . esc_html__( 'Show data table', 'wbd-real-estate-suite' ) . '</summary><table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Period', 'wbd-real-estate-suite' ) . '</th><th scope="col">' . esc_html__( 'Leads', 'wbd-real-estate-suite' ) . '</th></tr></thead><tbody>';
		foreach ( $points as $p ) {
			echo '<tr><td>' . esc_html( $p[0] ) . '</td><td>' . esc_html( number_format_i18n( $p[1] ) ) . '</td></tr>';
		}
		echo '</tbody></table></details>';
	}
}
