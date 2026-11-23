<?php
/**
 * Title: Agent Profile Landing Page
 * Slug: wbd-real-estate-suite/agent-profile
 * Categories: wbd-real-estate
 * Keywords: agent, profile, realtor
 * Description: Agent card, contact form, bio and the agent's current listings. Insert it into an agent profile.
 * Viewport Width: 1400
 * Post Types: wbd_agent, page
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(28px,4vw,48px)","bottom":"clamp(28px,4vw,48px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(28px,4vw,48px);padding-bottom:clamp(28px,4vw,48px)">
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"60%"} -->
<div class="wp-block-column" style="flex-basis:60%">
<!-- wp:wbdres/agent-card {"layout":"horizontal","showCount":true} /-->
</div>
<!-- /wp:column -->
<!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%">
<!-- wp:group {"className":"wbdres-form-card","layout":{"type":"default"}} -->
<div class="wp-block-group wbdres-form-card">
<!-- wp:wbdres/inquiry-form {"formType":"contact","title":<?php echo wp_json_encode( __( 'Send me a message', 'wbd-real-estate-suite' ) ); ?>} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'About me', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Share your experience, specialisms, the neighbourhoods you cover and the languages you speak.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'My listings', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:wbdres/property-listings {"agentId":-1,"perPage":6,"columns":3} /-->
</div>
<!-- /wp:group -->
