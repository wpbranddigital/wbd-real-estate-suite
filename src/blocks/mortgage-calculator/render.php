<?php
/**
 * Server render for wbdres/mortgage-calculator.
 *
 * @package Wbdres\RealEstate
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Inner content.
 * @var WP_Block  $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

use Wbdres\RealEstate\Blocks\Blocks;
use Wbdres\RealEstate\Settings;

$wbdres_price = (float) $attributes['price'];
if ( ! $wbdres_price ) {
	$wbdres_pid   = Blocks::property_id( $block, $attributes );
	$wbdres_price = $wbdres_pid ? (float) get_post_meta( $wbdres_pid, '_wbdres_price', true ) : 0;
}
\Wbdres\RealEstate\Frontend\Assets::enqueue_frontend();
$wbdres_html = \Wbdres\RealEstate\Frontend\View::mortgage(
	array(
		'price' => $wbdres_price,
		'rate'  => (float) $attributes['rate'] > 0 ? (float) $attributes['rate'] : (float) Settings::get( 'mortgage_rate', 6.5 ),
		'years' => (int) $attributes['years'] > 0 ? (int) $attributes['years'] : (int) Settings::get( 'mortgage_years', 30 ),
		'down'  => (float) $attributes['down'] >= 0 ? (float) $attributes['down'] : (float) Settings::get( 'mortgage_down', 20 ),
		'title' => $attributes['title'],
	)
);
echo '<div ' . get_block_wrapper_attributes() . '>' . $wbdres_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer.
