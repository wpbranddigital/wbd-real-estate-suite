<?php
/**
 * Automatic layouts for single properties/agents and archive templates
 * for both block themes and classic themes.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Frontend;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;
use Wbdres\RealEstate\Support\Property;

defined( 'ABSPATH' ) || exit;

/**
 * Templates.
 */
final class Templates {

	/**
	 * Re-entrancy guard.
	 *
	 * @var bool
	 */
	private static $rendering = false;

	/**
	 * Hooks.
	 */
	public function register() {
		add_filter( 'the_content', array( $this, 'single_content' ), 20 );
		add_filter( 'post_thumbnail_html', array( $this, 'hide_theme_thumbnail' ), 10, 2 );
		add_filter( 'render_block_core/post-featured-image', array( $this, 'hide_featured_block' ), 10, 3 );
		add_filter( 'template_include', array( $this, 'classic_archive_template' ) );
		add_action( 'init', array( $this, 'register_block_templates' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Should the automatic single layout apply right now?
	 *
	 * @param string $type Post type.
	 * @return bool
	 */
	private static function applies( $type ) {
		return ! self::$rendering
			&& Settings::get( 'auto_single_layout', 1 )
			&& is_singular( $type )
			&& in_the_loop()
			&& is_main_query()
			&& get_the_ID() === get_queried_object_id();
	}

	/**
	 * Wrap description in the automatic layout unless the editor already placed plugin blocks.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public function single_content( $content ) {
		if ( self::applies( Registrar::PROPERTY ) ) {
			$post = get_post();
			if ( $post && ( has_block( 'wbdres/property-details', $post ) || has_block( 'wbdres/property-gallery', $post ) || has_block( 'wbdres/property-field', $post ) ) ) {
				return $content;
			}
			$p = Property::get( $post );
			if ( ! $p ) {
				return $content;
			}
			self::$rendering = true;
			$html            = View::single_layout( $p, $content );
			self::$rendering = false;
			return $html;
		}

		if ( self::applies( Registrar::AGENT ) ) {
			$agent = get_post();
			if ( ! $agent || has_block( 'wbdres/agent-card', $agent ) ) {
				return $content;
			}
			self::$rendering = true;
			$html            = '<div class="wbdres-agent-page alignwide">';
			$html           .= '<div class="wbdres-agent-page__top">' . View::agent_card(
				$agent,
				array(
					'layout'  => 'horizontal',
					'link'    => false,
					'count'   => true,
					'heading' => 2,
				)
			) . View::inquiry_form(
				array(
					'type'    => 'contact',
					'agentId' => $agent->ID,
					/* translators: %s: agent name. */
					'title'   => sprintf( __( 'Contact %s', 'wbd-real-estate-suite' ), get_the_title( $agent ) ),
					'compact' => true,
				)
			) . '</div>';
			if ( trim( wp_strip_all_tags( $content ) ) ) {
				$html .= '<section class="wbdres-section"><h2 class="wbdres-section__title">' . esc_html__( 'About', 'wbd-real-estate-suite' ) . '</h2>' . $content . '</section>';
			}
			$listings = Listings::render(
				array(
					'agentId'        => $agent->ID,
					'perPage'        => 9,
					'columns'        => 3,
					'showPagination' => true,
				)
			);
			if ( false === strpos( $listings, 'wbdres-empty' ) ) {
				$html .= '<section class="wbdres-section"><h2 class="wbdres-section__title">' . esc_html__( 'Listings', 'wbd-real-estate-suite' ) . '</h2>' . $listings . '</section>';
			}
			self::$rendering = false;
			return $html . '</div>';
		}
		return $content;
	}

	/**
	 * The automatic layout has its own gallery, so suppress the theme's duplicate featured image.
	 *
	 * @param string $html    HTML.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function hide_theme_thumbnail( $html, $post_id ) {
		if ( self::$rendering || is_admin() || ! Settings::get( 'auto_single_layout', 1 ) ) {
			return $html;
		}
		if ( is_singular( Registrar::PROPERTY ) && get_queried_object_id() === (int) $post_id && ! has_block( 'wbdres/property-gallery', $post_id ) ) {
			return '';
		}
		return $html;
	}

	/**
	 * Same for block themes' Featured Image block.
	 *
	 * @param string    $content Content.
	 * @param array     $block   Block.
	 * @param \WP_Block $instance Instance.
	 * @return string
	 */
	public function hide_featured_block( $content, $block, $instance ) {
		if ( ! Settings::get( 'auto_single_layout', 1 ) || ! is_singular( Registrar::PROPERTY ) ) {
			return $content;
		}
		$post_id = isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : 0;
		return get_queried_object_id() === $post_id ? '' : $content;
	}

	/**
	 * Body classes.
	 *
	 * @param array $classes Classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( is_singular( Registrar::PROPERTY ) ) {
			$classes[] = 'wbdres-is-property';
		}
		return $classes;
	}

	/**
	 * Archive block markup shared by block and classic templates.
	 *
	 * @return string
	 */
	public static function archive_markup() {
		$title = '<!-- wp:query-title {"type":"archive","showPrefix":false,"className":"wbdres-archive__title"} /-->';
		return '<!-- wp:group {"tagName":"main","className":"wbdres-archive","layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} --><main class="wp-block-group wbdres-archive">'
			. $title
			. '<!-- wp:wbdres/property-search {"layout":"bar","align":"wide"} /-->'
			. '<!-- wp:wbdres/property-listings {"useMainQuery":true,"inheritSearch":true,"showToolbar":true,"columns":4,"align":"wide"} /-->'
			. '</main><!-- /wp:group -->';
	}

	/**
	 * Block theme templates (WordPress 6.7+). Themes can override in the Site Editor.
	 */
	public function register_block_templates() {
		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}
		$content = '<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->' . self::archive_markup() . '<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->';
		register_block_template(
			'wbd-real-estate-suite//archive-' . Registrar::PROPERTY,
			array(
				'title'       => __( 'Property Archive', 'wbd-real-estate-suite' ),
				'description' => __( 'Lists properties with search, filters, sorting and pagination.', 'wbd-real-estate-suite' ),
				'content'     => $content,
				'post_types'  => array(),
			)
		);
		$header = '<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->';
		$footer = '<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->';
		register_block_template(
			'wbd-real-estate-suite//single-' . Registrar::PROPERTY,
			array(
				'title'       => __( 'Single Property', 'wbd-real-estate-suite' ),
				'description' => __( 'Property gallery, price, key facts, description, map, agent and inquiry forms.', 'wbd-real-estate-suite' ),
				'content'     => $header . '<!-- wp:group {"tagName":"main","className":"wbdres-template","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} --><main class="wp-block-group wbdres-template" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:wbdres/property-details {"align":"wide"} /--></main><!-- /wp:group -->' . $footer,
			)
		);
		register_block_template(
			'wbd-real-estate-suite//single-' . Registrar::AGENT,
			array(
				'title'       => __( 'Single Agent', 'wbd-real-estate-suite' ),
				'description' => __( 'Agent profile with contact form and current listings.', 'wbd-real-estate-suite' ),
				'content'     => $header . '<!-- wp:group {"tagName":"main","className":"wbdres-template","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1320px","wideSize":"1320px"}} --><main class="wp-block-group wbdres-template" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:post-title {"level":1} /--><!-- wp:post-content {"layout":{"type":"constrained","contentSize":"1320px"}} /--></main><!-- /wp:group -->' . $footer,
			)
		);
		foreach ( array( Registrar::TYPE, Registrar::STATUS, Registrar::LOCATION, Registrar::FEATURE ) as $taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			register_block_template(
				'wbd-real-estate-suite//taxonomy-' . $taxonomy,
				array(
					/* translators: %s: taxonomy name. */
					'title'   => sprintf( __( 'Property %s Archive', 'wbd-real-estate-suite' ), $tax ? $tax->labels->singular_name : $taxonomy ),
					'content' => $content,
				)
			);
		}
	}

	/**
	 * Classic themes: plugin archive template, overridable from the theme.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function classic_archive_template( $template ) {
		if ( wp_is_block_theme() || ! Query::is_property_archive() ) {
			return $template;
		}
		$theme = locate_template( array( 'wbd-real-estate-suite/archive-property.php' ) );
		if ( $theme ) {
			return $theme;
		}
		// Respect a theme's own dedicated templates.
		$own = locate_template( array( 'archive-' . Registrar::PROPERTY . '.php' ) );
		if ( $own ) {
			return $own;
		}
		return WBDRES_PATH . 'templates/archive-property.php';
	}
}
