<?php
/**
 * Title: Property Comparison Page
 * Slug: wbd-real-estate-suite/compare
 * Categories: wbd-real-estate
 * Keywords: compare, comparison
 * Description: Side-by-side comparison table of up to four properties.
 * Viewport Width: 1400
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(32px,5vw,56px)","bottom":"clamp(32px,5vw,56px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(32px,5vw,56px);padding-bottom:clamp(32px,5vw,56px)">
<!-- wp:heading {"level":1,"className":"wbdres-page-title"} -->
<h1 class="wp-block-heading wbdres-page-title"><?php echo esc_html__( 'Compare properties', 'wbd-real-estate-suite' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"wbdres-lead"} -->
<p class="wbdres-lead"><?php echo esc_html__( 'Add properties with the compare button on any listing. Up to four can be compared side by side.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:wbdres/saved-properties {"mode":"compare","align":"wide"} /-->
</div>
<!-- /wp:group -->
