<?php
/**
 * Block pattern registration. Markup lives in /patterns/*.php.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Patterns;

defined( 'ABSPATH' ) || exit;

/**
 * Patterns.
 */
final class Patterns {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_patterns' ), 20 );
		add_filter( 'render_block_core/group', array( $this, 'enqueue_for_sections' ), 10, 2 );
	}

	/**
	 * Pattern definitions (translatable titles).
	 *
	 * @return array slug => [ title, description, post types ].
	 */
	private function definitions() {
		return array(
			'homepage'         => array( __( 'Real Estate Homepage', 'wbd-real-estate-suite' ), __( 'Hero with property search, browse by type, featured and latest listings, agents and a call to action.', 'wbd-real-estate-suite' ), array() ),
			'search-listing'   => array( __( 'Property Search and Listing Page', 'wbd-real-estate-suite' ), __( 'Sidebar filters with a results grid, sorting, grid/list toggle and pagination.', 'wbd-real-estate-suite' ), array() ),
			'single-property'  => array( __( 'Single Property Landing Page', 'wbd-real-estate-suite' ), __( 'Gallery, price, key facts, description, map and inquiry forms. Insert it into a property to design it with blocks.', 'wbd-real-estate-suite' ), array( 'wbd_property', 'page' ) ),
			'agent-directory'  => array( __( 'Agent Directory', 'wbd-real-estate-suite' ), __( 'Page header and a grid of all agents with contact buttons and listing counts.', 'wbd-real-estate-suite' ), array() ),
			'agent-profile'    => array( __( 'Agent Profile Landing Page', 'wbd-real-estate-suite' ), __( 'Agent card, contact form, bio and current listings. Insert it into an agent profile.', 'wbd-real-estate-suite' ), array( 'wbd_agent', 'page' ) ),
			'submit-property'  => array( __( 'Frontend Submission Page', 'wbd-real-estate-suite' ), __( 'Property submission form with a "My properties" dashboard.', 'wbd-real-estate-suite' ), array() ),
			'saved-properties' => array( __( 'Favorite Properties Page', 'wbd-real-estate-suite' ), __( 'Saved favorites and recently viewed properties, stored privately in the browser.', 'wbd-real-estate-suite' ), array() ),
			'compare'          => array( __( 'Property Comparison Page', 'wbd-real-estate-suite' ), __( 'Side-by-side comparison table of up to four properties.', 'wbd-real-estate-suite' ), array() ),
			'contact'          => array( __( 'Contact & Inquiry Page', 'wbd-real-estate-suite' ), __( 'Contact details next to a general inquiry form.', 'wbd-real-estate-suite' ), array() ),
			'cta'              => array( __( 'Property CTA Banner', 'wbd-real-estate-suite' ), __( 'Call to action inviting visitors to browse or contact an agent.', 'wbd-real-estate-suite' ), array() ),
		);
	}

	/**
	 * Register category and patterns.
	 */
	public function register_patterns() {
		register_block_pattern_category(
			'wbd-real-estate',
			array(
				'label'       => __( 'Real Estate', 'wbd-real-estate-suite' ),
				'description' => __( 'Ready-made real estate pages and sections.', 'wbd-real-estate-suite' ),
			)
		);
		foreach ( $this->definitions() as $slug => $def ) {
			$file = WBDRES_PATH . 'patterns/' . $slug . '.php';
			if ( ! is_readable( $file ) ) {
				continue;
			}
			ob_start();
			include $file;
			$content = (string) ob_get_clean();
			$args    = array(
				'title'         => $def[0],
				'description'   => $def[1],
				'content'       => $content,
				'categories'    => array( 'wbd-real-estate' ),
				'viewportWidth' => 1400,
			);
			if ( $def[2] ) {
				$args['postTypes'] = $def[2];
			}
			register_block_pattern( 'wbd-real-estate-suite/' . $slug, $args );
		}
	}

	/**
	 * Load the stylesheet for pattern sections made only of core blocks.
	 *
	 * @param string $content Content.
	 * @param array  $block   Block.
	 * @return string
	 */
	public function enqueue_for_sections( $content, $block ) {
		if ( ! empty( $block['attrs']['className'] ) && false !== strpos( (string) $block['attrs']['className'], 'wbdres-' ) ) {
			wp_enqueue_style( 'wbdres-frontend' );
		}
		return $content;
	}
}
