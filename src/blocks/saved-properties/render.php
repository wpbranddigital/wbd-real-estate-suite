<?php
/**
 * Server render for wbdres/saved-properties.
 *
 * @package Wbdres\RealEstate
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Inner content.
 * @var WP_Block  $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

use Wbdres\RealEstate\Frontend\View;

View::enqueue_interactivity();
$wbdres_mode  = in_array( $attributes['mode'], array( 'favorites', 'recent', 'compare' ), true ) ? $attributes['mode'] : 'favorites';
$wbdres_empty = array(
	'favorites' => __( 'You have no saved properties yet. Tap the heart on any property to save it here.', 'wbd-real-estate-suite' ),
	'recent'    => __( 'Properties you view will appear here.', 'wbd-real-estate-suite' ),
	'compare'   => __( 'Add up to 4 properties to compare using the compare button on property cards.', 'wbd-real-estate-suite' ),
);
$wbdres_html  = '<div class="wbdres-saved wbdres-saved--' . esc_attr( $wbdres_mode ) . '" data-wp-interactive="wbdres" ' . wp_interactivity_data_wp_context(
	array(
		'mode'    => $wbdres_mode,
		'items'   => array(),
		'loaded'  => false,
		'loading' => true,
	)
) . ' data-wp-init="callbacks.loadSaved">';
if ( $attributes['title'] ) {
	$wbdres_html .= '<h2 class="wbdres-section__title">' . esc_html( $attributes['title'] ) . '</h2>';
}
$wbdres_html .= '<div class="wbdres-skeleton-grid" data-wp-bind--hidden="!context.loading" aria-hidden="true"><span></span><span></span><span></span></div>';
$wbdres_html .= '<div class="wbdres-empty" hidden data-wp-bind--hidden="!state.isSavedEmpty">' . View::icon( 'compare' === $wbdres_mode ? 'compare' : 'heart' ) . '<p>' . esc_html( $wbdres_empty[ $wbdres_mode ] ) . '</p></div>';

if ( 'compare' === $wbdres_mode ) {
	$wbdres_rows  = array(
		'price'        => __( 'Price', 'wbd-real-estate-suite' ),
		'status'       => __( 'Status', 'wbd-real-estate-suite' ),
		'type'         => __( 'Type', 'wbd-real-estate-suite' ),
		'address'      => __( 'Location', 'wbd-real-estate-suite' ),
		'bedrooms'     => __( 'Bedrooms', 'wbd-real-estate-suite' ),
		'bathrooms'    => __( 'Bathrooms', 'wbd-real-estate-suite' ),
		'area'         => __( 'Area', 'wbd-real-estate-suite' ),
		'landArea'     => __( 'Land area', 'wbd-real-estate-suite' ),
		'garages'      => __( 'Garages', 'wbd-real-estate-suite' ),
		'yearBuilt'    => __( 'Year built', 'wbd-real-estate-suite' ),
		'featuresText' => __( 'Features', 'wbd-real-estate-suite' ),
	);
	$wbdres_html .= '<div class="wbdres-table-wrap" hidden data-wp-bind--hidden="state.isSavedEmpty"><table class="wbdres-compare"><caption class="screen-reader-text">' . esc_html__( 'Property comparison', 'wbd-real-estate-suite' ) . '</caption><thead><tr><td></td>'
		. '<template data-wp-each--item="context.items" data-wp-each-key="context.item.id"><th scope="col"><div class="wbdres-compare__head"><img data-wp-bind--src="context.item.image" data-wp-bind--hidden="!context.item.image" alt="" loading="lazy" /><a data-wp-bind--href="context.item.url" data-wp-text="context.item.title"></a><button type="button" class="wbdres-link-button" data-wp-on--click="actions.removeSaved">' . esc_html__( 'Remove', 'wbd-real-estate-suite' ) . '</button></div></th></template>'
		. '</tr></thead><tbody>';
	foreach ( $wbdres_rows as $wbdres_key => $wbdres_label ) {
		$wbdres_html .= '<tr><th scope="row">' . esc_html( $wbdres_label ) . '</th><template data-wp-each--item="context.items" data-wp-each-key="context.item.id"><td data-wp-text="context.item.' . esc_attr( $wbdres_key ) . '"></td></template></tr>';
	}
	$wbdres_html .= '</tbody></table></div>';
} else {
	$wbdres_html .= '<ul class="wbdres-grid" role="list" style="--wbdres-cols:' . esc_attr( min( 4, max( 1, (int) $attributes['columns'] ) ) ) . '"><template data-wp-each--item="context.items" data-wp-each-key="context.item.id"><li class="wbdres-grid__item"><article class="wbdres-card wbdres-card--modern">'
		. '<div class="wbdres-card__media" style="aspect-ratio:4/3"><a data-wp-bind--href="context.item.url" tabindex="-1" aria-hidden="true"><img class="wbdres-card__img" data-wp-bind--src="context.item.image" data-wp-bind--hidden="!context.item.image" alt="" loading="lazy" /></a><div class="wbdres-badges"><span class="wbdres-badge wbdres-badge--status" data-wp-text="context.item.status" data-wp-bind--hidden="!context.item.status"></span></div></div>'
		. '<div class="wbdres-card__body"><div class="wbdres-card__top"><p class="wbdres-card__price" data-wp-text="context.item.price"></p>'
		. ( 'favorites' === $wbdres_mode ? '<button type="button" class="wbdres-action wbdres-action--fav is-active" data-wp-on--click="actions.removeSaved" aria-label="' . esc_attr__( 'Remove from favorites', 'wbd-real-estate-suite' ) . '">' . View::icon( 'heart' ) . '</button>' : '' )
		. '</div><h3 class="wbdres-card__title"><a class="wbdres-card__link" data-wp-bind--href="context.item.url" data-wp-text="context.item.title"></a></h3><p class="wbdres-card__address">' . View::icon( 'pin' ) . '<span data-wp-text="context.item.address"></span></p>'
		. '<ul class="wbdres-specs wbdres-specs--icons"><li data-wp-bind--hidden="!context.item.bedrooms">' . View::icon( 'bed' ) . '<span data-wp-text="context.item.bedsText"></span></li><li data-wp-bind--hidden="!context.item.area">' . View::icon( 'area' ) . '<span data-wp-text="context.item.area"></span></li></ul>'
		. '</div></article></li></template></ul>';
	if ( 'favorites' === $wbdres_mode ) {
		$wbdres_html .= '<p class="wbdres-saved__clear" hidden data-wp-bind--hidden="state.isSavedEmpty"><button type="button" class="wbdres-link-button" data-wp-on--click="actions.clearSaved">' . esc_html__( 'Clear all favorites', 'wbd-real-estate-suite' ) . '</button></p>';
	}
}
$wbdres_html .= '</div>';
echo '<div ' . get_block_wrapper_attributes() . '>' . $wbdres_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
