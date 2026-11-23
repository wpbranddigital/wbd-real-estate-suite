<?php
/**
 * Server render for wbdres/property-video.
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
$wbdres_v = $wbdres_p ? View::video( $wbdres_p ) : '';
if ( $wbdres_v ) {
	\Wbdres\RealEstate\Frontend\Assets::enqueue_frontend();
	echo '<div ' . get_block_wrapper_attributes() . '>' . $wbdres_v . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
	echo Blocks::needs_property( __( 'Property Video (add a video URL in the property settings)', 'wbd-real-estate-suite' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
