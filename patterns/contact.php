<?php
/**
 * Title: Contact & Inquiry Page
 * Slug: wbd-real-estate-suite/contact
 * Categories: wbd-real-estate
 * Keywords: contact, inquiry, form
 * Description: Contact details next to a general inquiry form.
 * Viewport Width: 1400
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(36px,6vw,64px)","bottom":"clamp(36px,6vw,64px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(36px,6vw,64px);padding-bottom:clamp(36px,6vw,64px)">
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"45%"} -->
<div class="wp-block-column" style="flex-basis:45%">
<!-- wp:heading {"level":1,"className":"wbdres-page-title"} -->
<h1 class="wp-block-heading wbdres-page-title"><?php echo esc_html__( 'Get in touch', 'wbd-real-estate-suite' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"wbdres-lead"} -->
<p class="wbdres-lead"><?php echo esc_html__( 'Questions about buying, selling or renting? Send us a message and the right agent will reply.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:wbdres/agent-directory {"perPage":2,"columns":1,"showBio":false} /-->
</div>
<!-- /wp:column -->
<!-- wp:column {"width":"55%"} -->
<div class="wp-block-column" style="flex-basis:55%">
<!-- wp:group {"className":"wbdres-form-card","layout":{"type":"default"}} -->
<div class="wp-block-group wbdres-form-card">
<!-- wp:wbdres/inquiry-form {"formType":"contact","title":<?php echo wp_json_encode( __( 'Send us a message', 'wbd-real-estate-suite' ) ); ?>} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
