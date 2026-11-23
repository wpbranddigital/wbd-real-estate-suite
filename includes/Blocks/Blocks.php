<?php
/**
 * Block registration (block.json metadata from /build/blocks).
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Blocks;

use Wbdres\RealEstate\PostTypes\Registrar;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks.
 */
final class Blocks {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_blocks' ), 10 );
		add_filter( 'block_categories_all', array( $this, 'category' ), 10, 2 );
	}

	/**
	 * Register every block found in build/blocks.
	 */
	public function register_blocks() {
		$dirs = glob( WBDRES_PATH . 'build/blocks/*', GLOB_ONLYDIR );
		if ( ! $dirs ) {
			return;
		}
		foreach ( $dirs as $dir ) {
			if ( is_readable( $dir . '/block.json' ) ) {
				register_block_type( $dir );
			}
		}
		foreach ( glob( WBDRES_PATH . 'build/blocks/*/index.js' ) ? glob( WBDRES_PATH . 'build/blocks/*/index.js' ) : array() as $script ) {
			$handle = 'wbdres-' . basename( dirname( $script ) ) . '-editor-script';
			wp_set_script_translations( $handle, 'wbd-real-estate-suite' );
		}
	}

	/**
	 * Block inserter category.
	 *
	 * @param array $categories Categories.
	 * @return array
	 */
	public function category( $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'wbd-real-estate',
				'title' => __( 'Real Estate', 'wbd-real-estate-suite' ),
				'icon'  => null,
			)
		);
		return $categories;
	}

	/**
	 * Resolve the property ID for a block (context → attribute → current post).
	 *
	 * @param \WP_Block|null $block Block instance.
	 * @param array          $attrs Attributes.
	 * @return int
	 */
	public static function property_id( $block, array $attrs = array() ) {
		if ( ! empty( $attrs['propertyId'] ) ) {
			return (int) $attrs['propertyId'];
		}
		if ( $block instanceof \WP_Block && ! empty( $block->context['postId'] ) && Registrar::PROPERTY === get_post_type( (int) $block->context['postId'] ) ) {
			return (int) $block->context['postId'];
		}
		$id = get_the_ID();
		return ( $id && Registrar::PROPERTY === get_post_type( $id ) ) ? (int) $id : 0;
	}

	/**
	 * Editor placeholder shown when a single-property block has no property context.
	 *
	 * @param string $label Block label.
	 * @return string
	 */
	public static function needs_property( $label ) {
		if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! is_admin() ) {
			return '';
		}
		/* translators: %s: block name. */
		return '<div class="wbdres-placeholder">' . esc_html( sprintf( __( '%s: shows data of the current property. Use it on a property page or in the single property template, or pick a property in the block settings.', 'wbd-real-estate-suite' ), $label ) ) . '</div>';
	}

	/**
	 * Render post content without our own layout blocks (prevents recursion).
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public static function description_html( \WP_Post $post ) {
		static $depth = 0;
		if ( $depth > 0 ) {
			return '';
		}
		++$depth;
		$html = '';
		foreach ( parse_blocks( $post->post_content ) as $parsed ) {
			if ( ! empty( $parsed['blockName'] ) && 0 === strpos( $parsed['blockName'], 'wbdres/' ) ) {
				continue;
			}
			$html .= render_block( $parsed );
		}
		--$depth;
		return has_blocks( $post->post_content ) ? $html : wpautop( $html );
	}
}
