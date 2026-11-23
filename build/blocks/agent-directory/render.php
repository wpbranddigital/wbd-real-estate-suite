<?php
/**
 * Server render for wbdres/agent-directory.
 *
 * @package Wbdres\RealEstate
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Inner content.
 * @var WP_Block  $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

\Wbdres\RealEstate\Frontend\Assets::enqueue_frontend();
$wbdres_q = new WP_Query(
	array(
		'post_type'      => \Wbdres\RealEstate\PostTypes\Registrar::AGENT,
		'post_status'    => 'publish',
		'posts_per_page' => min( 100, max( 1, (int) $attributes['perPage'] ) ),
		'orderby'        => in_array( $attributes['orderBy'], array( 'title', 'date', 'menu_order' ), true ) ? $attributes['orderBy'] : 'title',
		'order'          => 'date' === $attributes['orderBy'] ? 'DESC' : 'ASC',
		'no_found_rows'  => true,
	)
);
if ( ! $wbdres_q->have_posts() ) {
	echo '<div ' . get_block_wrapper_attributes() . '><div class="wbdres-empty">' . esc_html__( 'No agents yet.', 'wbd-real-estate-suite' ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$wbdres_out = '<ul class="wbdres-grid wbdres-agent-grid" role="list" style="--wbdres-cols:' . esc_attr( min( 4, max( 1, (int) $attributes['columns'] ) ) ) . '">';
foreach ( $wbdres_q->posts as $wbdres_agent ) {
	$wbdres_out .= '<li class="wbdres-grid__item">' . \Wbdres\RealEstate\Frontend\View::agent_card(
		$wbdres_agent,
		array(
			'layout'  => 'card',
			'bio'     => ! empty( $attributes['showBio'] ),
			'contact' => ! empty( $attributes['showContact'] ),
			'social'  => false,
			'count'   => ! empty( $attributes['showCount'] ),
		)
	) . '</li>';
}
wp_reset_postdata();
echo '<div ' . get_block_wrapper_attributes() . '>' . $wbdres_out . '</ul></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
