<?php
/**
 * Title: Single Property Landing Page
 * Slug: wbd-real-estate-suite/single-property
 * Categories: wbd-real-estate
 * Keywords: single, property, details, landing
 * Description: Gallery, price, key facts, description, map and a sticky inquiry form. Insert it into a property to design it with blocks.
 * Viewport Width: 1400
 * Post Types: wbd_property, page
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(24px,3vw,40px)","bottom":"clamp(24px,3vw,40px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(24px,3vw,40px);padding-bottom:clamp(24px,3vw,40px)">
<!-- wp:wbdres/property-gallery {"layout":"hero","align":"wide"} /-->
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%">
<!-- wp:wbdres/property-field {"field":"status"} /-->
<!-- wp:wbdres/property-field {"field":"title","headingLevel":1} /-->
<!-- wp:wbdres/property-field {"field":"address"} /-->
<!-- wp:wbdres/property-field {"field":"price"} /-->
<!-- wp:wbdres/property-field {"field":"keyfacts"} /-->
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'About this home', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Describe the property here: layout, light, renovations, outdoor space and what makes the neighbourhood special.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'Facts & features', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:wbdres/property-field {"field":"specs"} /-->
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'What\'s special', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:wbdres/property-field {"field":"features"} /-->
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'Location', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:wbdres/property-map /-->
<!-- wp:wbdres/property-video /-->
<!-- wp:wbdres/mortgage-calculator /-->
</div>
<!-- /wp:column -->
<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%">
<!-- wp:wbdres/agent-card {"layout":"horizontal","showBio":false} /-->
<!-- wp:wbdres/inquiry-form {"title":<?php echo wp_json_encode( __( 'Ask about this property', 'wbd-real-estate-suite' ) ); ?>} /-->
<!-- wp:wbdres/inquiry-form {"formType":"viewing","title":<?php echo wp_json_encode( __( 'Schedule a viewing', 'wbd-real-estate-suite' ) ); ?>} /-->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'Similar properties', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:wbdres/property-listings {"similarTo":-1,"perPage":3,"columns":3,"showPagination":false} /-->
</div>
<!-- /wp:group -->
