<?php
/**
 * Title: Real Estate Homepage
 * Slug: wbd-real-estate-suite/homepage
 * Categories: wbd-real-estate
 * Keywords: home, real estate, hero, search
 * Description: Hero with property search, browse by type, featured and latest listings, agents and a call to action.
 * Viewport Width: 1400
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"wbdres-hero","align":"full","style":{"spacing":{"padding":{"top":"clamp(56px,8vw,112px)","bottom":"clamp(56px,8vw,112px)"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group wbdres-hero alignfull" style="padding-top:clamp(56px,8vw,112px);padding-bottom:clamp(56px,8vw,112px)">
<!-- wp:heading {"level":1,"textAlign":"center","className":"wbdres-hero__title"} -->
<h1 class="wp-block-heading has-text-align-center wbdres-hero__title"><?php echo esc_html__( 'Find a home you will love', 'wbd-real-estate-suite' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","className":"wbdres-hero__text"} -->
<p class="has-text-align-center wbdres-hero__text"><?php echo esc_html__( 'Browse handpicked properties for sale and rent, talk to local experts and book viewings in minutes.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:wbdres/property-search {"layout":"bar","align":"wide"} /-->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(32px,5vw,56px)","bottom":"clamp(32px,5vw,56px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(32px,5vw,56px);padding-bottom:clamp(32px,5vw,56px)">
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'Browse by property type', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:wbdres/property-terms {"taxonomy":"wbd_property_type","layout":"cards","align":"wide"} /-->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(16px,2.5vw,28px)","bottom":"clamp(16px,2.5vw,28px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(16px,2.5vw,28px);padding-bottom:clamp(16px,2.5vw,28px)">
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'Featured properties', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"wbdres-lead"} -->
<p class="wbdres-lead"><?php echo esc_html__( 'Hand-selected listings our agents recommend this week.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:wbdres/property-listings {"featuredOnly":true,"perPage":4,"columns":4,"showPagination":false,"align":"wide"} /-->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(16px,2.5vw,28px)","bottom":"clamp(16px,2.5vw,28px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(16px,2.5vw,28px);padding-bottom:clamp(16px,2.5vw,28px)">
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'New on the market', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:wbdres/property-listings {"orderBy":"newest","perPage":8,"columns":4,"showPagination":false,"cardStyle":"overlay","align":"wide"} /-->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_post_type_archive_link( 'wbd_property' ) ); ?>"><?php echo esc_html__( 'View all properties', 'wbd-real-estate-suite' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(16px,2.5vw,28px)","bottom":"clamp(16px,2.5vw,28px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(16px,2.5vw,28px);padding-bottom:clamp(16px,2.5vw,28px)">
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'Popular locations', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:wbdres/property-terms {"taxonomy":"wbd_property_location","layout":"chips","topLevel":false,"number":12} /-->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(32px,5vw,56px)","bottom":"clamp(32px,5vw,56px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(32px,5vw,56px);padding-bottom:clamp(32px,5vw,56px)">
<!-- wp:heading {"textAlign":"center","className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading has-text-align-center wbdres-section-heading"><?php echo esc_html__( 'Meet our agents', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","className":"wbdres-lead"} -->
<p class="has-text-align-center wbdres-lead"><?php echo esc_html__( 'Local experts who answer quickly and know every street.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:wbdres/agent-directory {"perPage":3,"columns":3,"showBio":false,"align":"wide"} /-->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"wbdres-cta-section","align":"wide","style":{"spacing":{"padding":{"top":"clamp(32px,5vw,56px)","bottom":"clamp(32px,5vw,56px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-cta-section alignwide" style="padding-top:clamp(32px,5vw,56px);padding-bottom:clamp(32px,5vw,56px)">
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%">
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'Thinking of selling or renting out?', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"wbdres-lead"} -->
<p class="wbdres-lead"><?php echo esc_html__( 'Get a free valuation and a marketing plan from an agent who knows your area.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%">
<!-- wp:wbdres/inquiry-form {"formType":"contact","title":"","buttonText":<?php echo wp_json_encode( __( 'Request a free valuation', 'wbd-real-estate-suite' ) ); ?>} /-->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
