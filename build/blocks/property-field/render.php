<?php
/**
 * Server render for wbdres/property-field.
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
use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Support\Property;

$wbdres_p = Property::get( Blocks::property_id( $block, $attributes ) );
if ( ! $wbdres_p ) {
	echo Blocks::needs_property( __( 'Property Field', 'wbd-real-estate-suite' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
\Wbdres\RealEstate\Frontend\Assets::enqueue_frontend();
$wbdres_field = isset( $attributes['field'] ) ? $attributes['field'] : 'price';
$wbdres_html  = '';
switch ( $wbdres_field ) {
	case 'title':
		$wbdres_tag  = 'h' . min( 6, max( 1, (int) $attributes['headingLevel'] ) );
		$wbdres_html = '<' . $wbdres_tag . ' class="wbdres-field-title">' . esc_html( $wbdres_p->title() ) . '</' . $wbdres_tag . '>';
		break;
	case 'price':
		$wbdres_html = '<p class="wbdres-single__price">' . View::price_html( $wbdres_p ) . '</p>';
		break;
	case 'address':
		$wbdres_addr = $wbdres_p->address();
		$wbdres_html = $wbdres_addr ? '<p class="wbdres-single__address">' . View::icon( 'pin' ) . '<span>' . esc_html( $wbdres_addr ) . '</span></p>' : '';
		break;
	case 'status':
		$wbdres_html = View::badges( $wbdres_p );
		break;
	case 'specs':
		$wbdres_html = View::specs( $wbdres_p, true, ! empty( $attributes['showLabels'] ) ? 'facts' : 'icons' );
		break;
	case 'keyfacts':
		$wbdres_html = View::specs( $wbdres_p, false, 'icons' );
		break;
	case 'features':
		$wbdres_html = View::features( $wbdres_p );
		break;
	case 'type':
		$wbdres_t    = $wbdres_p->term( Registrar::TYPE );
		$wbdres_html = $wbdres_t ? '<p class="wbdres-single__type">' . esc_html( $wbdres_t->name ) . '</p>' : '';
		break;
	case 'reference':
		$wbdres_ref  = (string) $wbdres_p->meta( 'reference' );
		$wbdres_html = $wbdres_ref ? '<p class="wbdres-reference">' . esc_html__( 'Ref.', 'wbd-real-estate-suite' ) . ' ' . esc_html( $wbdres_ref ) . '</p>' : '';
		break;
}
if ( '' !== $wbdres_html ) {
	echo '<div ' . get_block_wrapper_attributes( array( 'class' => 'wbdres-field-' . sanitize_html_class( $wbdres_field ) ) ) . '>' . $wbdres_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
}
