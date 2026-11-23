<?php
/**
 * Server render for wbdres/inquiry-form.
 *
 * @package Wbdres\RealEstate
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Inner content.
 * @var WP_Block  $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

use Wbdres\RealEstate\PostTypes\Registrar;

$wbdres_pid = (int) $attributes['propertyId'];
$wbdres_aid = (int) $attributes['agentId'];
$wbdres_ctx = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
if ( ! $wbdres_pid && Registrar::PROPERTY === get_post_type( $wbdres_ctx ) ) {
	$wbdres_pid = $wbdres_ctx;
}
if ( ! $wbdres_aid && Registrar::AGENT === get_post_type( $wbdres_ctx ) ) {
	$wbdres_aid = $wbdres_ctx;
}
\Wbdres\RealEstate\Frontend\Assets::enqueue_frontend();
$wbdres_html = \Wbdres\RealEstate\Frontend\View::inquiry_form(
	array(
		'type'       => $attributes['formType'],
		'propertyId' => $wbdres_pid,
		'agentId'    => $wbdres_aid,
		'title'      => $attributes['title'],
		'buttonText' => $attributes['buttonText'],
		'showPhone'  => ! empty( $attributes['showPhone'] ),
		'phoneReq'   => ! empty( $attributes['phoneRequired'] ),
	)
);
echo '<div ' . get_block_wrapper_attributes( array( 'id' => 'wbdres-contact' ) ) . '>' . $wbdres_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer.
