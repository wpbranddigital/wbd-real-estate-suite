<?php
/**
 * Title: Favorite Properties Page
 * Slug: wbd-real-estate-suite/saved-properties
 * Categories: wbd-real-estate
 * Keywords: favorites, wishlist, saved
 * Description: Saved favorites and recently viewed properties, stored privately in the visitor's browser.
 * Viewport Width: 1400
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(32px,5vw,56px)","bottom":"clamp(32px,5vw,56px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(32px,5vw,56px);padding-bottom:clamp(32px,5vw,56px)">
<!-- wp:heading {"level":1,"className":"wbdres-page-title"} -->
<h1 class="wp-block-heading wbdres-page-title"><?php echo esc_html__( 'Saved properties', 'wbd-real-estate-suite' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:wbdres/saved-properties {"mode":"favorites","columns":3,"align":"wide"} /-->
<!-- wp:heading {"className":"wbdres-section-heading"} -->
<h2 class="wp-block-heading wbdres-section-heading"><?php echo esc_html__( 'Recently viewed', 'wbd-real-estate-suite' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:wbdres/saved-properties {"mode":"recent","columns":4,"align":"wide"} /-->
</div>
<!-- /wp:group -->
