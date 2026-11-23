<?php
/**
 * Server render for wbdres/property-terms.
 *
 * @package Wbdres\RealEstate
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Inner content.
 * @var WP_Block  $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

\Wbdres\RealEstate\Frontend\Assets::enqueue_frontend();
$wbdres_tax  = in_array( $attributes['taxonomy'], array( 'wbd_property_type', 'wbd_property_location', 'wbd_property_status', 'wbd_property_feature' ), true ) ? $attributes['taxonomy'] : 'wbd_property_type';
$wbdres_args = array(
	'taxonomy'     => $wbdres_tax,
	'hide_empty'   => ! empty( $attributes['hideEmpty'] ),
	'number'       => min( 50, max( 1, (int) $attributes['number'] ) ),
	'orderby'      => 'count',
	'order'        => 'DESC',
	'hierarchical' => false,
);
if ( ! empty( $attributes['topLevel'] ) ) {
	$wbdres_args['parent'] = 0;
}
$wbdres_terms = get_terms( $wbdres_args );
if ( ! $wbdres_terms || is_wp_error( $wbdres_terms ) ) {
	echo \Wbdres\RealEstate\Blocks\Blocks::needs_property( __( 'Browse by Type or Location (no terms with listings yet)', 'wbd-real-estate-suite' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$wbdres_layout = 'chips' === $attributes['layout'] ? 'chips' : 'cards';
$wbdres_out    = '<ul class="wbdres-terms wbdres-terms--' . esc_attr( $wbdres_layout ) . '" role="list">';
foreach ( $wbdres_terms as $wbdres_term ) {
	$wbdres_link = get_term_link( $wbdres_term );
	if ( is_wp_error( $wbdres_link ) ) {
		continue;
	}
	/* translators: %s: number of properties. */
	$wbdres_count = ! empty( $attributes['showCount'] ) ? '<span class="wbdres-terms__count">' . esc_html( sprintf( _n( '%s property', '%s properties', $wbdres_term->count, 'wbd-real-estate-suite' ), number_format_i18n( $wbdres_term->count ) ) ) . '</span>' : '';
	$wbdres_out  .= '<li><a href="' . esc_url( $wbdres_link ) . '" class="wbdres-terms__item">' . ( 'cards' === $wbdres_layout ? \Wbdres\RealEstate\Frontend\View::icon( 'wbd_property_location' === $wbdres_tax ? 'pin' : 'home' ) : '' ) . '<span class="wbdres-terms__name">' . esc_html( $wbdres_term->name ) . '</span>' . $wbdres_count . '</a></li>';
}
echo '<div ' . get_block_wrapper_attributes() . '>' . $wbdres_out . '</ul></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
