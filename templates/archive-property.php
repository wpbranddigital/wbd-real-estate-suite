<?php
/**
 * Property archive template for classic themes.
 *
 * Override: copy to yourtheme/wbd-real-estate-suite/archive-property.php
 *
 * @package Wbdres\RealEstate
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wbdres-classic-wrap">
	<?php
	echo do_blocks( \Wbdres\RealEstate\Frontend\Templates::archive_markup() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered by escaped block callbacks.
	?>
</div>
<?php
get_footer();
