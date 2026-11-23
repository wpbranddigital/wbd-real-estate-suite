<?php
/**
 * Server render for wbdres/property-details.
 *
 * @package Wbdres\RealEstate
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Inner content.
 * @var WP_Block  $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

use Wbdres\RealEstate\Blocks\Blocks;
use Wbdres\RealEstate\Frontend\View;
use Wbdres\RealEstate\Support\Property;

$wbdres_p = Property::get( Blocks::property_id( $block, $attributes ) );
if ( ! $wbdres_p ) {
	echo Blocks::needs_property( __( 'Property Details', 'wbd-real-estate-suite' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$wbdres_desc = ! empty( $attributes['showDescription'] ) ? Blocks::description_html( $wbdres_p->post ) : '';
$wbdres_html = View::single_layout(
	$wbdres_p,
	$wbdres_desc,
	array(
		'gallery'  => ! empty( $attributes['showGallery'] ),
		'header'   => ! empty( $attributes['showHeader'] ),
		'title'    => ! empty( $attributes['showTitle'] ),
		'specs'    => ! empty( $attributes['showSpecs'] ),
		'features' => ! empty( $attributes['showFeatures'] ),
		'video'    => ! empty( $attributes['showVideo'] ),
		'map'      => ! empty( $attributes['showMap'] ),
		'agent'    => ! empty( $attributes['showAgent'] ),
		'inquiry'  => ! empty( $attributes['showInquiry'] ),
		'viewing'  => ! empty( $attributes['showViewing'] ),
		'mortgage' => ! empty( $attributes['showMortgage'] ),
		'similar'  => ! empty( $attributes['showSimilar'] ),
		'cta'      => ! empty( $attributes['showCta'] ),
	)
);
echo '<div ' . get_block_wrapper_attributes() . '>' . $wbdres_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer.
