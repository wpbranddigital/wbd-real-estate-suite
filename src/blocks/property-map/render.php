<?php
/**
 * Server render for wbdres/property-map.
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
	echo Blocks::needs_property( __( 'Property Map', 'wbd-real-estate-suite' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
\Wbdres\RealEstate\Frontend\Assets::enqueue_frontend();
$wbdres_map = View::map( $wbdres_p, (int) $attributes['height'], (int) $attributes['zoom'] );
if ( $wbdres_map ) {
	echo '<div ' . get_block_wrapper_attributes() . '>' . $wbdres_map . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
	echo Blocks::needs_property( __( 'Property Map (add latitude & longitude in the property settings)', 'wbd-real-estate-suite' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
