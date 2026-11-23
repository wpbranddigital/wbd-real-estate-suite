<?php
/**
 * Title: Property CTA Banner
 * Slug: wbd-real-estate-suite/cta
 * Categories: wbd-real-estate
 * Keywords: cta, call to action, banner
 * Description: Full-width call to action inviting visitors to contact an agent or list a property.
 * Viewport Width: 1400
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;

// "Talk to an agent" goes to the demo Contact page when it exists, otherwise to the agent archive.
$wbdres_demo    = get_option( 'wbdres_demo_pages', array() );
$wbdres_contact = ! empty( $wbdres_demo['contact'] ) ? get_permalink( (int) $wbdres_demo['contact'] ) : '';
$wbdres_contact = $wbdres_contact ? $wbdres_contact : get_post_type_archive_link( 'wbd_agent' );
$wbdres_contact = $wbdres_contact ? $wbdres_contact : home_url( '/' );
?>
<!-- wp:group {"className":"wbdres-cta-section","align":"full","style":{"spacing":{"padding":{"top":"clamp(48px,7vw,88px)","bottom":"clamp(48px,7vw,88px)"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group wbdres-cta-section alignfull" style="padding-top:clamp(48px,7vw,88px);padding-bottom:clamp(48px,7vw,88px)">
<!-- wp:heading {"textAlign":"center","className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading has-text-align-center wbdres-section-heading"><?php echo esc_html__( 'Ready to find your next home?', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","className":"wbdres-lead"} -->
<p class="has-text-align-center wbdres-lead"><?php echo esc_html__( 'Tell us what you are looking for and we will send matching properties before they hit the market.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_post_type_archive_link( 'wbd_property' ) ); ?>"><?php echo esc_html__( 'Browse properties', 'wbd-real-estate-suite' ); ?></a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $wbdres_contact ); ?>"><?php echo esc_html__( 'Talk to an agent', 'wbd-real-estate-suite' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
