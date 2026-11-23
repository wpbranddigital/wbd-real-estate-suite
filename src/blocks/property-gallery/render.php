<?php
/**
 * Server render for wbdres/property-gallery.
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
	echo Blocks::needs_property( __( 'Property Gallery', 'wbd-real-estate-suite' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
\Wbdres\RealEstate\Frontend\Assets::enqueue_frontend();
$wbdres_html = View::gallery(
	$wbdres_p,
	array(
		'layout'   => $attributes['layout'],
		'lightbox' => ! empty( $attributes['lightbox'] ),
		'max'      => (int) $attributes['max'],
	)
);
echo '<div ' . get_block_wrapper_attributes() . '>' . $wbdres_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer.
