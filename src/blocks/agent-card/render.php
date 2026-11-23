<?php
/**
 * Server render for wbdres/agent-card.
 *
 * @package Wbdres\RealEstate
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Inner content.
 * @var WP_Block  $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

use Wbdres\RealEstate\Frontend\View;
use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Support\Property;

$wbdres_agent_id = (int) $attributes['agentId'];
if ( ! $wbdres_agent_id ) {
	$wbdres_ctx = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
	if ( Registrar::AGENT === get_post_type( $wbdres_ctx ) ) {
		$wbdres_agent_id = $wbdres_ctx;
	} elseif ( Registrar::PROPERTY === get_post_type( $wbdres_ctx ) ) {
		$wbdres_prop     = Property::get( $wbdres_ctx );
		$wbdres_agent    = $wbdres_prop ? $wbdres_prop->agent() : null;
		$wbdres_agent_id = $wbdres_agent ? $wbdres_agent->ID : 0;
	}
}
$wbdres_agent = $wbdres_agent_id ? get_post( $wbdres_agent_id ) : null;
if ( ! $wbdres_agent || Registrar::AGENT !== $wbdres_agent->post_type ) {
	echo \Wbdres\RealEstate\Blocks\Blocks::needs_property( __( 'Agent Profile (choose an agent, or use on an agent or property page)', 'wbd-real-estate-suite' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
\Wbdres\RealEstate\Frontend\Assets::enqueue_frontend();
$wbdres_html = View::agent_card(
	$wbdres_agent,
	array(
		'layout'  => $attributes['layout'],
		'bio'     => ! empty( $attributes['showBio'] ),
		'contact' => ! empty( $attributes['showContact'] ),
		'social'  => ! empty( $attributes['showSocial'] ),
		'count'   => ! empty( $attributes['showCount'] ),
	)
);
echo '<div ' . get_block_wrapper_attributes() . '>' . $wbdres_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer.
