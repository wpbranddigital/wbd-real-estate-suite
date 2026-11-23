<?php
/**
 * Title: Property Search and Listing Page
 * Slug: wbd-real-estate-suite/search-listing
 * Categories: wbd-real-estate
 * Keywords: search, listing, filters, results
 * Description: Sidebar filters with a results grid, sorting, grid/list toggle and pagination.
 * Viewport Width: 1400
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(28px,4vw,48px)","bottom":"clamp(28px,4vw,48px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(28px,4vw,48px);padding-bottom:clamp(28px,4vw,48px)">
<!-- wp:heading {"level":1,"className":"wbdres-page-title"} -->
<h1 class="wp-block-heading wbdres-page-title"><?php echo esc_html__( 'Properties for sale and rent', 'wbd-real-estate-suite' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"wbdres-lead"} -->
<p class="wbdres-lead"><?php echo esc_html__( 'Use the filters to narrow down by type, location, price, rooms and amenities.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"300px"} -->
<div class="wp-block-column" style="flex-basis:300px">
<!-- wp:wbdres/property-search {"layout":"sidebar"} /-->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:wbdres/property-listings {"inheritSearch":true,"showToolbar":true,"perPage":12,"columns":3,"layout":"grid"} /-->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
