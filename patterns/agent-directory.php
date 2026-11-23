<?php
/**
 * Title: Agent Directory
 * Slug: wbd-real-estate-suite/agent-directory
 * Categories: wbd-real-estate
 * Keywords: agents, team, realtors
 * Description: Page header and a grid of all agents with contact buttons and listing counts.
 * Viewport Width: 1400
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"wbdres-pattern","align":"wide","style":{"spacing":{"padding":{"top":"clamp(36px,6vw,64px)","bottom":"clamp(36px,6vw,64px)"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} -->
<div class="wp-block-group wbdres-pattern alignwide" style="padding-top:clamp(36px,6vw,64px);padding-bottom:clamp(36px,6vw,64px)">
<!-- wp:heading {"level":1,"textAlign":"center","className":"wbdres-page-title"} -->
<h1 class="wp-block-heading has-text-align-center wbdres-page-title"><?php echo esc_html__( 'Our agents', 'wbd-real-estate-suite' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","className":"wbdres-lead"} -->
<p class="has-text-align-center wbdres-lead"><?php echo esc_html__( 'Experienced, responsive and local. Choose an agent and get in touch directly.', 'wbd-real-estate-suite' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:wbdres/agent-directory {"perPage":24,"columns":3,"align":"wide"} /-->
</div>
<!-- /wp:group -->
