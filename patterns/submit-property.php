<?php
/**
 * Title: Frontend Submission Page
 * Slug: wbd-real-estate-suite/submit-property
 * Categories: wbd-real-estate
 * Keywords: submit, frontend, dashboard
 * Description: Intro text and the frontend property submission form with a "My properties" dashboard.
 * Viewport Width: 1400
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"wbdres-pattern","style":{"spacing":{"padding":{"top":"clamp(32px,5vw,56px)","bottom":"clamp(32px,5vw,56px)"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group wbdres-pattern" style="padding-top:clamp(32px,5vw,56px);padding-bottom:clamp(32px,5vw,56px)">
<!-- wp:heading {"level":1,"className":"wbdres-page-title"} -->
<h1 class="wp-block-heading wbdres-page-title"><?php echo esc_html__( 'List your property', 'wbd-real-estate-suite' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"wbdres-lead"} -->
<p class="wbdres-lead"><?php echo esc_html__( 'Submit your property for free. Our team reviews every listing before it goes live, usually within one business day.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:wbdres/property-submission {"showMySubmissions":true} /-->
</div>
<!-- /wp:group -->
