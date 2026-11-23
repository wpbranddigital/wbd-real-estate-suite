<?php
/**
 * Shared, escaped frontend markup used by blocks and automatic layouts.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Frontend;

use Wbdres\RealEstate\PostTypes\Registrar;
use Wbdres\RealEstate\Settings;
use Wbdres\RealEstate\Support\Format;
use Wbdres\RealEstate\Support\Property;

defined( 'ABSPATH' ) || exit;

/**
 * Rendering helpers. Every method returns an escaped HTML string.
 */
final class View {

	/**
	 * Inline SVG icons (decorative; aria-hidden).
	 *
	 * @param string $name Icon name.
	 * @return string
	 */
	public static function icon( $name ) {
		$paths = array(
			'bed'      => '<path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6M3 14h18M3 18v2M21 18v2M6 10V7a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>',
			'bath'     => '<path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-3ZM6 12V6a2 2 0 0 1 4 0M7 19l-1 2M17 19l1 2"/>',
			'area'     => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
			'garage'   => '<path d="M3 21V9l9-5 9 5v12M7 21v-7h10v7M7 17h10"/>',
			'land'     => '<path d="M3 20h18M5 20l4-9 3 5 2-3 5 7"/>',
			'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
			'home'     => '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z"/>',
			'tag'      => '<path d="M3 12V4h8l10 10-8 8L3 12Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
			'pin'      => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
			'heart'    => '<path d="M12 20s-7.5-4.6-9.3-9.2C1.5 7.6 3.6 4.5 7 4.5c2 0 3.3 1.1 5 3 1.7-1.9 3-3 5-3 3.4 0 5.5 3.1 4.3 6.3C19.5 15.4 12 20 12 20Z"/>',
			'compare'  => '<path d="M7 4 3 8l4 4M3 8h14M17 12l4 4-4 4M21 16H7"/>',
			'share'    => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.3 13.3 7.4 4.4M15.7 6.3l-7.4 4.4"/>',
			'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
			'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
			'chat'     => '<path d="M4 20l1.5-4A8 8 0 1 1 9 19.5L4 20Z"/>',
			'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
			'check'    => '<path d="m5 12 4 4 10-10"/>',
			'play'     => '<circle cx="12" cy="12" r="9"/><path d="m10 8 6 4-6 4V8Z"/>',
			'expand'   => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
			'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
			'prev'     => '<path d="m15 5-7 7 7 7"/>',
			'next'     => '<path d="m9 5 7 7-7 7"/>',
			'image'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 17-5-5-9 8"/>',
		);
		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}
		return '<svg class="wbdres-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
	}

	/**
	 * Enqueue the shared interactive script module and seed its state/config.
	 */
	public static function enqueue_interactivity() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		wp_enqueue_script_module( '@wbdres/view' );
		wp_enqueue_style( 'wbdres-frontend' );
		wp_interactivity_state(
			'wbdres',
			array(
				'isFavorite' => false,
				'isCompared' => false,
				'favCount'   => 0,
				'cmpCount'   => 0,
			)
		);
		wp_interactivity_config(
			'wbdres',
			array(
				'restUrl'    => esc_url_raw( rest_url( 'wbdres/v1/' ) ),
				'trackViews' => (bool) Settings::get( 'enable_views', 1 ),
				'maxCompare' => 4,
				'i18n'       => array(
					'sending'      => __( 'Sending…', 'wbd-real-estate-suite' ),
					'sent'         => __( 'Thank you! Your message has been sent. We will get back to you shortly.', 'wbd-real-estate-suite' ),
					'error'        => __( 'Sorry, something went wrong. Please try again.', 'wbd-real-estate-suite' ),
					'compareLimit' => __( 'You can compare up to 4 properties.', 'wbd-real-estate-suite' ),
					'empty'        => __( 'Nothing saved yet.', 'wbd-real-estate-suite' ),
					'priceOnReq'   => __( 'Price on request', 'wbd-real-estate-suite' ),
				),
			)
		);
	}

	/**
	 * Status + featured badges.
	 *
	 * @param Property $p Property.
	 * @return string
	 */
	public static function badges( Property $p ) {
		$out    = '';
		$status = $p->term( Registrar::STATUS );
		if ( $p->is_featured() ) {
			$out .= '<span class="wbdres-badge wbdres-badge--featured">' . esc_html__( 'Featured', 'wbd-real-estate-suite' ) . '</span>';
		}
		if ( $status ) {
			$out .= '<span class="wbdres-badge wbdres-badge--status wbdres-status-' . esc_attr( $status->slug ) . '">' . esc_html( $status->name ) . '</span>';
		}
		return $out ? '<div class="wbdres-badges">' . $out . '</div>' : '';
	}

	/**
	 * Specification list.
	 *
	 * @param Property $p       Property.
	 * @param bool     $all     Extended specs.
	 * @param string   $variant icons|stacked|facts.
	 * @return string
	 */
	public static function specs( Property $p, $all = false, $variant = 'icons' ) {
		$specs = $p->specs( $all );
		if ( ! $specs ) {
			return '';
		}
		if ( true === $variant ) {
			$variant = 'facts';
		}
		$variant = in_array( $variant, array( 'icons', 'stacked', 'facts' ), true ) ? $variant : 'icons';
		$html    = '<ul class="wbdres-specs wbdres-specs--' . esc_attr( $variant ) . '">';
		foreach ( $specs as $s ) {
			$html .= '<li class="wbdres-spec wbdres-spec--' . esc_attr( $s['key'] ) . '">';
			if ( 'stacked' === $variant ) {
				$html .= '<span class="wbdres-spec__label">' . esc_html( $s['label'] ) . '</span><span class="wbdres-spec__value">' . esc_html( $s['value'] ) . '</span>';
			} elseif ( 'facts' === $variant ) {
				$html .= self::icon( $s['icon'] ) . '<span class="wbdres-spec__text"><span class="wbdres-spec__label">' . esc_html( $s['label'] ) . '</span><strong class="wbdres-spec__value">' . esc_html( $s['value'] ) . '</strong></span>';
			} else {
				$short = isset( $s['short'] ) ? ' ' . $s['short'] : '';
				$html .= self::icon( $s['icon'] ) . '<span class="wbdres-spec__value">' . esc_html( $s['value'] . $short ) . '</span>';
				if ( '' === $short ) {
					$html .= '<span class="screen-reader-text"> ' . esc_html( $s['label'] ) . '</span>';
				}
			}
			$html .= '</li>';
		}
		return $html . '</ul>';
	}

	/**
	 * Favourite + compare buttons.
	 *
	 * @param Property $p Property.
	 * @return string
	 */
	public static function actions( Property $p ) {
		$fav = (bool) Settings::get( 'enable_favorites', 1 );
		$cmp = (bool) Settings::get( 'enable_compare', 1 );
		if ( ! $fav && ! $cmp ) {
			return '';
		}
		self::enqueue_interactivity();
		$title = $p->title();
		$html  = '<div class="wbdres-actions" data-wp-interactive="wbdres" ' . wp_interactivity_data_wp_context( array( 'id' => $p->id() ) ) . '>';
		if ( $fav ) {
			$html .= '<button type="button" class="wbdres-action wbdres-action--fav" data-wp-on--click="actions.toggleFavorite" data-wp-bind--aria-pressed="state.isFavorite" data-wp-class--is-active="state.isFavorite" aria-pressed="false">'
				. self::icon( 'heart' )
				/* translators: %s: property title. */
				. '<span class="screen-reader-text">' . esc_html( sprintf( __( 'Save %s to favorites', 'wbd-real-estate-suite' ), $title ) ) . '</span></button>';
		}
		if ( $cmp ) {
			$html .= '<button type="button" class="wbdres-action wbdres-action--compare" data-wp-on--click="actions.toggleCompare" data-wp-bind--aria-pressed="state.isCompared" data-wp-class--is-active="state.isCompared" aria-pressed="false">'
				. self::icon( 'compare' )
				/* translators: %s: property title. */
				. '<span class="screen-reader-text">' . esc_html( sprintf( __( 'Add %s to comparison', 'wbd-real-estate-suite' ), $title ) ) . '</span></button>';
		}
		return $html . '</div>';
	}

	/**
	 * Property card.
	 *
	 * Styles: "modern" (price in body + round favourite button + icon specs),
	 * "overlay" (price pill on photo + labelled spec columns), "list" (horizontal).
	 *
	 * @param Property $p    Property.
	 * @param array    $opts Options.
	 * @return string
	 */
	public static function card( Property $p, array $opts = array() ) {
		$o = wp_parse_args(
			$opts,
			array(
				'style'        => 'modern',
				'image'        => true,
				'price'        => true,
				'address'      => true,
				'specs'        => true,
				'excerpt'      => false,
				'agent'        => false,
				'badges'       => true,
				'actions'      => true,
				'type'         => false,
				'ratio'        => '4/3',
				'headingLevel' => 3,
				'eager'        => false,
			)
		);

		$style  = in_array( $o['style'], array( 'modern', 'overlay', 'list' ), true ) ? $o['style'] : 'modern';
		$tag    = 'h' . min( 6, max( 2, (int) $o['headingLevel'] ) );
		$url    = $p->url();
		$title  = $p->title();
		$images = $p->image_ids();
		$ratio  = in_array( $o['ratio'], array( '4/3', '16/9', '3/2', '1/1', '3/4' ), true ) ? $o['ratio'] : '4/3';
		$price  = '<p class="wbdres-card__price">' . self::price_html( $p ) . '</p>';

		$html = '<article class="wbdres-card wbdres-card--' . esc_attr( $style ) . '" aria-labelledby="wbdres-card-title-' . esc_attr( $p->id() ) . '">';

		if ( $o['image'] ) {
			$html .= '<div class="wbdres-card__media" style="aspect-ratio:' . esc_attr( $ratio ) . '">';
			$html .= '<a href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">';
			if ( $images ) {
				$html .= wp_get_attachment_image(
					$images[0],
					'medium_large',
					false,
					array(
						'class'         => 'wbdres-card__img',
						'alt'           => '',
						'loading'       => $o['eager'] ? 'eager' : 'lazy',
						'fetchpriority' => $o['eager'] ? 'high' : 'auto',
						'sizes'         => 'list' === $style ? '(max-width: 600px) 100vw, 320px' : '(max-width: 600px) 100vw, (max-width: 1024px) 50vw, 25vw',
					)
				);
			} else {
				$html .= '<span class="wbdres-card__placeholder">' . self::icon( 'image' ) . '</span>';
			}
			$html .= '</a>';
			if ( $o['badges'] ) {
				$html .= self::badges( $p );
			}
			if ( 'overlay' === $style && $o['price'] ) {
				$html .= '<span class="wbdres-card__pill">' . self::price_html( $p ) . '</span>';
			}
			if ( $o['actions'] && 'modern' !== $style ) {
				$html .= self::actions( $p );
			}
			$html .= '</div>';
		}

		$html .= '<div class="wbdres-card__body">';
		if ( 'overlay' !== $style && $o['price'] ) {
			$html .= '<div class="wbdres-card__top">' . $price . ( ( $o['actions'] && 'modern' === $style ) ? self::actions( $p ) : '' ) . '</div>';
		} elseif ( 'modern' === $style && $o['actions'] ) {
			$html .= '<div class="wbdres-card__top">' . self::actions( $p ) . '</div>';
		}
		$html .= '<' . $tag . ' class="wbdres-card__title" id="wbdres-card-title-' . esc_attr( $p->id() ) . '"><a href="' . esc_url( $url ) . '" class="wbdres-card__link">' . esc_html( $title ) . '</a></' . $tag . '>';

		if ( $o['address'] ) {
			$address = $p->address();
			if ( $address ) {
				$html .= '<p class="wbdres-card__address">' . ( 'overlay' === $style ? '' : self::icon( 'pin' ) ) . '<span>' . esc_html( $address ) . '</span></p>';
			}
		}
		if ( $o['excerpt'] || 'list' === $style ) {
			$excerpt = wp_trim_words( get_the_excerpt( $p->post ), 'list' === $style ? 22 : 16 );
			if ( $excerpt ) {
				$html .= '<p class="wbdres-card__excerpt">' . esc_html( $excerpt ) . '</p>';
			}
		}
		if ( $o['specs'] ) {
			$html .= self::specs( $p, false, 'overlay' === $style ? 'stacked' : 'icons' );
		}

		$footer = '';
		if ( $o['type'] ) {
			$type = $p->term( Registrar::TYPE );
			if ( $type ) {
				$footer .= '<span class="wbdres-card__type">' . esc_html( $type->name ) . '</span>';
			}
		}
		if ( $o['agent'] ) {
			$agent = $p->agent();
			if ( $agent ) {
				$footer .= '<span class="wbdres-card__agent">' . get_the_post_thumbnail(
					$agent,
					'thumbnail',
					array(
						'alt'     => '',
						'loading' => 'lazy',
					)
				) . '<span>' . esc_html( get_the_title( $agent ) ) . '</span></span>';
			}
		}
		if ( $footer ) {
			$html .= '<div class="wbdres-card__footer">' . $footer . '</div>';
		}
		$html .= '</div></article>';

		/**
		 * Filter a rendered property card.
		 *
		 * @param string   $html Card HTML.
		 * @param Property $p    Property.
		 * @param array    $o    Options.
		 */
		return apply_filters( 'wbdres_property_card_html', $html, $p, $o );
	}

	/**
	 * Price with a de-emphasised suffix (e.g. "$2,500 /month").
	 *
	 * @param Property $p Property.
	 * @return string
	 */
	public static function price_html( Property $p ) {
		$suffix = (string) $p->meta( 'price_suffix' );
		$on_req = (bool) $p->meta( 'price_on_request' ) || (float) $p->meta( 'price' ) <= 0;
		$main   = Format::price( (float) $p->meta( 'price' ), '', $on_req );
		return '<span class="wbdres-price">' . esc_html( $main ) . '</span>' . ( ( $suffix && ! $on_req ) ? '<span class="wbdres-price__suffix"> ' . esc_html( $suffix ) . '</span>' : '' );
	}

	/**
	 * Image alt text with sensible fallback.
	 *
	 * @param int    $id       Attachment ID.
	 * @param string $fallback Fallback text.
	 * @return string
	 */
	public static function alt( $id, $fallback ) {
		$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		return '' !== $alt ? $alt : $fallback;
	}

	/**
	 * Gallery with accessible native-dialog lightbox.
	 *
	 * @param Property $p    Property.
	 * @param array    $opts Options: layout (hero|grid|stack), lightbox, max.
	 * @return string
	 */
	public static function gallery( Property $p, array $opts = array() ) {
		$o   = wp_parse_args(
			$opts,
			array(
				'layout'   => 'hero',
				'lightbox' => true,
				'max'      => 30,
			)
		);
		$ids = array_slice( $p->image_ids(), 0, max( 1, (int) $o['max'] ) );
		if ( ! $ids ) {
			return '<div class="wbdres-gallery wbdres-gallery--empty"><div class="wbdres-card__placeholder">' . self::icon( 'image' ) . '<span>' . esc_html__( 'Photos coming soon', 'wbd-real-estate-suite' ) . '</span></div></div>';
		}
		$title  = $p->title();
		$layout = in_array( $o['layout'], array( 'hero', 'grid', 'stack' ), true ) ? $o['layout'] : 'hero';
		$count  = count( $ids );
		$images = array();
		foreach ( $ids as $id ) {
			$src = wp_get_attachment_image_src( $id, 'large' );
			if ( $src ) {
				$images[] = array(
					'src' => $src[0],
					'alt' => self::alt( $id, $title ),
				);
			}
		}

		if ( $o['lightbox'] ) {
			self::enqueue_interactivity();
		}

		$html = '<div class="wbdres-gallery wbdres-gallery--' . esc_attr( $layout ) . ' wbdres-gallery--count-' . esc_attr( min( $count, 5 ) ) . '"';
		if ( $o['lightbox'] ) {
			$html .= ' data-wp-interactive="wbdres" ' . wp_interactivity_data_wp_context(
				array(
					'images' => $images,
					'index'  => 0,
					'open'   => false,
				)
			);
		}
		$html   .= '>';
		$html   .= '<ul class="wbdres-gallery__grid" role="list">';
		$visible = 'hero' === $layout ? 5 : $count;
		foreach ( $ids as $i => $id ) {
			if ( $i >= $visible ) {
				break;
			}
			$size  = ( 0 === $i ) ? 'large' : 'medium_large';
			$img   = wp_get_attachment_image(
				$id,
				$size,
				false,
				array(
					'class'         => 'wbdres-gallery__img',
					'alt'           => self::alt( $id, $title ),
					'loading'       => 0 === $i ? 'eager' : 'lazy',
					'fetchpriority' => 0 === $i ? 'high' : 'auto',
					'sizes'         => 0 === $i ? '(max-width: 782px) 100vw, 66vw' : '(max-width: 782px) 50vw, 25vw',
				)
			);
			$html .= '<li class="wbdres-gallery__item">';
			if ( $o['lightbox'] ) {
				$more  = ( 'hero' === $layout && 4 === $i && $count > 5 ) ? '<span class="wbdres-gallery__more" aria-hidden="true">+' . esc_html( $count - 5 ) . '</span>' : '';
				$full  = wp_get_attachment_image_url( $id, 'full' );
				$html .= '<a class="wbdres-gallery__open" href="' . esc_url( $full ? $full : '#' ) . '" data-wp-on--click="actions.openLightbox" data-index="' . esc_attr( $i ) . '">'
					. $img . $more
					/* translators: 1: image number, 2: total images. */
					. '<span class="screen-reader-text">' . esc_html( sprintf( __( 'Open photo %1$d of %2$d', 'wbd-real-estate-suite' ), $i + 1, $count ) ) . '</span></a>';
			} else {
				$html .= $img;
			}
			$html .= '</li>';
		}
		$html .= '</ul>';

		if ( $o['lightbox'] && $count > 1 ) {
			/* translators: %d: number of photos. */
			$html .= '<button type="button" class="wbdres-gallery__all" data-wp-on--click="actions.openLightbox" data-index="0">' . self::icon( 'image' ) . esc_html( sprintf( _n( 'View %d photo', 'View all %d photos', $count, 'wbd-real-estate-suite' ), $count ) ) . '</button>';
		}

		if ( $o['lightbox'] ) {
			$html .= '<dialog class="wbdres-lightbox" aria-label="' . esc_attr__( 'Photo viewer', 'wbd-real-estate-suite' ) . '" data-wp-watch="callbacks.syncDialog" data-wp-on--close="actions.closeLightbox" data-wp-on--keydown="actions.lightboxKeys">'
				. '<button type="button" class="wbdres-lightbox__close" data-wp-on--click="actions.closeLightbox">' . self::icon( 'close' ) . '<span class="screen-reader-text">' . esc_html__( 'Close', 'wbd-real-estate-suite' ) . '</span></button>'
				. '<button type="button" class="wbdres-lightbox__nav wbdres-lightbox__prev" data-wp-on--click="actions.prevImage">' . self::icon( 'prev' ) . '<span class="screen-reader-text">' . esc_html__( 'Previous photo', 'wbd-real-estate-suite' ) . '</span></button>'
				. '<figure class="wbdres-lightbox__figure"><img data-wp-bind--src="state.currentSrc" data-wp-bind--alt="state.currentAlt" src="' . esc_url( $images[0]['src'] ) . '" alt="' . esc_attr( $images[0]['alt'] ) . '" /><figcaption class="wbdres-lightbox__count" data-wp-text="state.counter" aria-live="polite"></figcaption></figure>'
				. '<button type="button" class="wbdres-lightbox__nav wbdres-lightbox__next" data-wp-on--click="actions.nextImage">' . self::icon( 'next' ) . '<span class="screen-reader-text">' . esc_html__( 'Next photo', 'wbd-real-estate-suite' ) . '</span></button>'
				. '</dialog>';
		}
		return $html . '</div>';
	}

	/**
	 * Header section: status, title, address, price, key facts, monthly estimate, actions.
	 *
	 * @param Property $p          Property.
	 * @param bool     $show_title Output the H1.
	 * @return string
	 */
	public static function header( Property $p, $show_title = true ) {
		$status = $p->term( Registrar::STATUS );
		$html   = '<header class="wbdres-single__header"><div class="wbdres-single__heading">';
		$html  .= '<div class="wbdres-single__meta">';
		if ( $status ) {
			$html .= '<span class="wbdres-status-dot wbdres-status-' . esc_attr( $status->slug ) . '">' . esc_html( $status->name ) . '</span>';
		}
		if ( $p->is_featured() ) {
			$html .= '<span class="wbdres-badge wbdres-badge--featured">' . esc_html__( 'Featured', 'wbd-real-estate-suite' ) . '</span>';
		}
		$type = $p->term( Registrar::TYPE );
		if ( $type ) {
			$html .= '<span class="wbdres-single__type">' . esc_html( $type->name ) . '</span>';
		}
		$html .= '</div>';
		$html .= '<p class="wbdres-single__price">' . self::price_html( $p ) . '</p>';
		if ( $show_title ) {
			$html .= '<h1 class="wbdres-single__title">' . esc_html( $p->title() ) . '</h1>';
		}
		$address = $p->address();
		if ( $address ) {
			$html .= '<p class="wbdres-single__address">' . self::icon( 'pin' ) . '<span>' . esc_html( $address ) . '</span></p>';
		}
		$estimate = self::monthly_estimate( $p );
		if ( $estimate ) {
			/* translators: %s: estimated monthly payment. */
			$html .= '<p class="wbdres-single__estimate">' . esc_html( sprintf( __( 'Est. %s/mo', 'wbd-real-estate-suite' ), $estimate ) ) . ' <a href="#wbdres-mortgage">' . esc_html__( 'Calculate payment', 'wbd-real-estate-suite' ) . '</a></p>';
		}
		$html .= '</div>';

		// Key facts: big numbers.
		$facts = '';
		foreach ( $p->specs( false ) as $spec ) {
			$value  = 'area' === $spec['key'] ? Format::number( (float) $p->meta( 'area' ), 0 ) : $spec['value'];
			$label  = 'area' === $spec['key'] ? ( Settings::area_units()[ Settings::get( 'area_unit', 'sqft' ) ] ?? '' ) : $spec['label'];
			$facts .= '<li><strong>' . esc_html( $value ) . '</strong><span>' . esc_html( $label ) . '</span></li>';
		}
		$html .= '<div class="wbdres-single__side">';
		if ( $facts ) {
			$html .= '<ul class="wbdres-keyfacts">' . $facts . '</ul>';
		}
		$html .= '<div class="wbdres-single__tools">' . self::actions( $p )
			. '<button type="button" class="wbdres-chip-button" data-wp-on--click="actions.share" data-url="' . esc_url( $p->url() ) . '" data-title="' . esc_attr( $p->title() ) . '">' . self::icon( 'share' ) . '<span>' . esc_html__( 'Share', 'wbd-real-estate-suite' ) . '</span></button>'
			. '<span class="wbdres-share-status" role="status" aria-live="polite" data-wp-text="context.shareMsg"></span></div>';
		$html .= '</div></header>';
		return $html;
	}

	/**
	 * Monthly payment estimate using default mortgage settings.
	 *
	 * @param Property $p Property.
	 * @return string Formatted amount or ''.
	 */
	public static function monthly_estimate( Property $p ) {
		$price = (float) $p->meta( 'price' );
		if ( $price <= 0 || $p->meta( 'price_on_request' ) || self::is_rental( $p ) ) {
			return '';
		}
		$loan  = $price * ( 1 - (float) Settings::get( 'mortgage_down', 20 ) / 100 );
		$rate  = (float) Settings::get( 'mortgage_rate', 6.5 ) / 100 / 12;
		$n     = max( 1, (int) Settings::get( 'mortgage_years', 30 ) * 12 );
		$month = $rate > 0 ? $loan * $rate / ( 1 - pow( 1 + $rate, -$n ) ) : $loan / $n;
		return Format::price( round( $month ) );
	}

	/**
	 * Features list.
	 *
	 * @param Property $p Property.
	 * @return string
	 */
	public static function features( Property $p ) {
		$terms = $p->terms( Registrar::FEATURE );
		if ( ! $terms ) {
			return '';
		}
		$html = '<ul class="wbdres-features" role="list">';
		foreach ( $terms as $t ) {
			$html .= '<li class="wbdres-chip">' . self::icon( 'check' ) . '<span>' . esc_html( $t->name ) . '</span></li>';
		}
		return $html . '</ul>';
	}

	/**
	 * Video embed / virtual tour link.
	 *
	 * @param Property $p Property.
	 * @return string
	 */
	public static function video( Property $p ) {
		$video = (string) $p->meta( 'video_url' );
		$tour  = (string) $p->meta( 'tour_url' );
		if ( ! $video && ! $tour ) {
			return '';
		}
		$html = '';
		if ( $video ) {
			$embed = wp_oembed_get( $video, array( 'width' => 960 ) );
			if ( $embed ) {
				$html .= '<div class="wbdres-video">' . $embed . '</div>';
			} else {
				$html .= '<p><a class="wbdres-button wbdres-button--outline" href="' . esc_url( $video ) . '" target="_blank" rel="noopener noreferrer">' . self::icon( 'play' ) . esc_html__( 'Watch video', 'wbd-real-estate-suite' ) . '</a></p>';
			}
		}
		if ( $tour ) {
			$html .= '<p><a class="wbdres-button wbdres-button--outline" href="' . esc_url( $tour ) . '" target="_blank" rel="noopener noreferrer">' . self::icon( 'expand' ) . esc_html__( 'Open virtual tour', 'wbd-real-estate-suite' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'wbd-real-estate-suite' ) . '</span></a></p>';
		}
		return $html;
	}

	/**
	 * Privacy-friendly OpenStreetMap embed (loads only after consent click).
	 *
	 * @param Property $p      Property.
	 * @param int      $height Height in px.
	 * @param int      $zoom   Approximate zoom (affects bbox).
	 * @return string
	 */
	public static function map( Property $p, $height = 360, $zoom = 15 ) {
		if ( ! Settings::get( 'enable_map', 1 ) || $p->meta( 'hide_map' ) ) {
			return '';
		}
		$lat = (float) $p->meta( 'lat' );
		$lng = (float) $p->meta( 'lng' );
		if ( ! $lat && ! $lng ) {
			return '';
		}
		$zoom   = min( 19, max( 3, (int) $zoom ) );
		$delta  = 360 / pow( 2, $zoom ) * 1.2;
		$bbox   = implode( ',', array( $lng - $delta, $lat - $delta / 2, $lng + $delta, $lat + $delta / 2 ) );
		$src    = add_query_arg(
			array(
				'bbox'   => rawurlencode( $bbox ),
				'layer'  => 'mapnik',
				'marker' => rawurlencode( $lat . ',' . $lng ),
			),
			'https://www.openstreetmap.org/export/embed.html'
		);
		$link   = 'https://www.openstreetmap.org/?mlat=' . rawurlencode( (string) $lat ) . '&mlon=' . rawurlencode( (string) $lng ) . '#map=' . $zoom . '/' . rawurlencode( (string) $lat ) . '/' . rawurlencode( (string) $lng );
		$height = min( 900, max( 200, (int) $height ) );
		$title  = __( 'Property location map', 'wbd-real-estate-suite' );

		if ( ! Settings::get( 'map_consent', 1 ) ) {
			return '<div class="wbdres-map" style="height:' . esc_attr( $height ) . 'px"><iframe title="' . esc_attr( $title ) . '" src="' . esc_url( $src ) . '" loading="lazy" referrerpolicy="no-referrer"></iframe></div><p class="wbdres-map__link"><a href="' . esc_url( $link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View larger map', 'wbd-real-estate-suite' ) . '</a></p>';
		}

		self::enqueue_interactivity();
		return '<div class="wbdres-map" style="height:' . esc_attr( $height ) . 'px" data-wp-interactive="wbdres" ' . wp_interactivity_data_wp_context(
			array(
				'src'    => $src,
				'loaded' => false,
			)
		) . '>'
			. '<div class="wbdres-map__consent" data-wp-bind--hidden="context.loaded">' . self::icon( 'pin' )
			. '<p>' . esc_html__( 'This map is provided by OpenStreetMap. Loading it connects your browser to openstreetmap.org.', 'wbd-real-estate-suite' ) . '</p>'
			. '<button type="button" class="wbdres-button" data-wp-on--click="actions.loadMap">' . esc_html__( 'Load map', 'wbd-real-estate-suite' ) . '</button></div>'
			. '<iframe title="' . esc_attr( $title ) . '" hidden data-wp-bind--hidden="!context.loaded" data-wp-bind--src="state.mapSrc" loading="lazy" referrerpolicy="no-referrer"></iframe>'
			. '</div><p class="wbdres-map__link"><a href="' . esc_url( $link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View on OpenStreetMap', 'wbd-real-estate-suite' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'wbd-real-estate-suite' ) . '</span></a></p>';
	}

	/**
	 * Agent card.
	 *
	 * @param \WP_Post $agent Agent post.
	 * @param array    $opts  Options: layout (card|horizontal|compact), bio, contact, social, link.
	 * @return string
	 */
	public static function agent_card( \WP_Post $agent, array $opts = array() ) {
		$o      = wp_parse_args(
			$opts,
			array(
				'layout'  => 'card',
				'bio'     => false,
				'contact' => true,
				'social'  => true,
				'link'    => true,
				'count'   => false,
				'heading' => 3,
				'wa_text' => '',
			)
		);
		$tag    = 'h' . min( 6, max( 2, (int) $o['heading'] ) );
		$name   = get_the_title( $agent );
		$url    = get_permalink( $agent );
		$job    = (string) get_post_meta( $agent->ID, '_wbdres_agent_title', true );
		$agency = (string) get_post_meta( $agent->ID, '_wbdres_agent_agency', true );
		$phone  = (string) get_post_meta( $agent->ID, '_wbdres_agent_phone', true );
		$wa     = WhatsApp::number_for( $agent );
		$layout = in_array( $o['layout'], array( 'card', 'horizontal', 'compact' ), true ) ? $o['layout'] : 'card';

		$html  = '<div class="wbdres-agent wbdres-agent--' . esc_attr( $layout ) . '">';
		$html .= '<div class="wbdres-agent__photo">';
		if ( has_post_thumbnail( $agent ) ) {
			$html .= get_the_post_thumbnail(
				$agent,
				'medium',
				array(
					/* translators: %s: agent name. */
					'alt'     => sprintf( __( 'Photo of %s', 'wbd-real-estate-suite' ), $name ),
					'loading' => 'lazy',
				)
			);
		} else {
			$initials = '';
			foreach ( array_slice( preg_split( '/\s+/', trim( wp_strip_all_tags( $name ) ) ), 0, 2 ) as $part ) {
				$initials .= Format::upper( mb_substr( $part, 0, 1 ) );
			}
			$html .= '<span class="wbdres-agent__initials" aria-hidden="true">' . esc_html( $initials ) . '</span>';
		}
		$html .= '</div><div class="wbdres-agent__body">';
		$html .= '<' . $tag . ' class="wbdres-agent__name">' . ( $o['link'] ? '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>' : esc_html( $name ) ) . '</' . $tag . '>';
		if ( $job || $agency ) {
			$html .= '<p class="wbdres-agent__role">' . esc_html( implode( ' · ', array_filter( array( $job, $agency ) ) ) ) . '</p>';
		}
		if ( $o['count'] ) {
			$count = self::agent_property_count( $agent->ID );
			/* translators: %s: number of listings. */
			$html .= '<p class="wbdres-agent__count">' . esc_html( sprintf( _n( '%s listing', '%s listings', $count, 'wbd-real-estate-suite' ), number_format_i18n( $count ) ) ) . '</p>';
		}
		if ( $o['bio'] && has_excerpt( $agent ) ) {
			$html .= '<p class="wbdres-agent__bio">' . esc_html( get_the_excerpt( $agent ) ) . '</p>';
		}
		if ( $o['contact'] && ( $phone || $wa ) ) {
			$html .= '<div class="wbdres-agent__contact">';
			if ( $phone ) {
				$html .= '<a class="wbdres-button wbdres-button--outline" href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . self::icon( 'phone' ) . '<span>' . esc_html( $phone ) . '</span></a>';
			}
			if ( $wa ) {
				$html .= WhatsApp::button( $wa, (string) $o['wa_text'] );
			}
			$html .= '</div>';
		}
		if ( $o['social'] ) {
			$links = array(
				'_wbdres_agent_website'   => __( 'Website', 'wbd-real-estate-suite' ),
				'_wbdres_agent_linkedin'  => 'LinkedIn',
				'_wbdres_agent_facebook'  => 'Facebook',
				'_wbdres_agent_instagram' => 'Instagram',
				'_wbdres_agent_x'         => 'X',
			);
			$items = '';
			foreach ( $links as $key => $label ) {
				$href = (string) get_post_meta( $agent->ID, $key, true );
				if ( $href ) {
					$items .= '<li><a href="' . esc_url( $href ) . '" target="_blank" rel="noopener noreferrer me">' . esc_html( $label ) . '</a></li>';
				}
			}
			if ( $items ) {
				/* translators: %s: agent name. */
				$html .= '<ul class="wbdres-agent__social" aria-label="' . esc_attr( sprintf( __( '%s online', 'wbd-real-estate-suite' ), $name ) ) . '">' . $items . '</ul>';
			}
		}
		return $html . '</div></div>';
	}

	/**
	 * Count published properties for an agent (cached).
	 *
	 * @param int $agent_id Agent ID.
	 * @return int
	 */
	public static function agent_property_count( $agent_id ) {
		$key   = 'agent_count_' . (int) $agent_id;
		$count = wp_cache_get( $key, 'wbdres' );
		if ( false === $count ) {
			$q     = new \WP_Query(
				array(
					'post_type'              => Registrar::PROPERTY,
					'post_status'            => 'publish',
					'posts_per_page'         => 1,
					'fields'                 => 'ids',
					'no_found_rows'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => '_wbdres_agent_id',
							'value' => (int) $agent_id,
							'type'  => 'NUMERIC',
						),
					),
				)
			);
			$count = (int) $q->found_posts;
			wp_cache_set( $key, $count, 'wbdres', HOUR_IN_SECONDS );
		}
		return (int) $count;
	}

	/**
	 * Inquiry / viewing / contact form. Works without JavaScript (admin-post fallback)
	 * and is enhanced to submit via the REST API.
	 *
	 * @param array $args Args.
	 * @return string
	 */
	public static function inquiry_form( array $args = array() ) {
		static $n = 0;
		++$n;
		$a = wp_parse_args(
			$args,
			array(
				'type'       => 'inquiry',
				'propertyId' => 0,
				'agentId'    => 0,
				'title'      => '',
				'buttonText' => '',
				'showPhone'  => true,
				'phoneReq'   => false,
				'message'    => '',
				'compact'    => false,
			)
		);
		self::enqueue_interactivity();

		$type = in_array( $a['type'], array( 'inquiry', 'viewing', 'contact' ), true ) ? $a['type'] : 'inquiry';
		$pid  = (int) $a['propertyId'];
		$aid  = (int) $a['agentId'];
		$p    = $pid ? Property::get( $pid ) : null;

		if ( '' === $a['message'] ) {
			if ( 'viewing' === $type && $p ) {
				/* translators: %s: property title. */
				$a['message'] = sprintf( __( 'I would like to schedule a viewing of "%s".', 'wbd-real-estate-suite' ), $p->title() );
			} elseif ( $p ) {
				/* translators: %s: property title. */
				$a['message'] = sprintf( __( 'Hello, I am interested in "%s". Please send me more information.', 'wbd-real-estate-suite' ), $p->title() );
			}
		}
		$button = $a['buttonText'] ? $a['buttonText'] : ( 'viewing' === $type ? __( 'Request viewing', 'wbd-real-estate-suite' ) : __( 'Send message', 'wbd-real-estate-suite' ) );
		$uid    = 'wbdres-f' . $n;
		$sent   = isset( $_GET['wbdres_sent'] ) ? sanitize_key( wp_unslash( $_GET['wbdres_sent'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag.
		$user   = wp_get_current_user();

		$html = '<div class="wbdres-form-wrap" data-wp-interactive="wbdres" ' . wp_interactivity_data_wp_context(
			array(
				'status'  => '',
				'message' => '',
			)
		) . '>';
		if ( $a['title'] ) {
			$html .= '<h2 class="wbdres-form__title" id="' . esc_attr( $uid ) . '-title">' . esc_html( $a['title'] ) . '</h2>';
		}
		if ( 'ok' === $sent ) {
			$html .= '<div class="wbdres-notice wbdres-notice--success" role="status">' . esc_html__( 'Thank you! Your message has been sent. We will get back to you shortly.', 'wbd-real-estate-suite' ) . '</div>';
		} elseif ( 'error' === $sent ) {
			$html .= '<div class="wbdres-notice wbdres-notice--error" role="alert">' . esc_html__( 'Sorry, your message could not be sent. Please check the form and try again.', 'wbd-real-estate-suite' ) . '</div>';
		}

		$html .= '<form class="wbdres-form' . ( $a['compact'] ? ' wbdres-form--compact' : '' ) . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" novalidate data-wp-on--submit="actions.submitInquiry" data-wp-class--is-busy="state.isSending"' . ( $a['title'] ? ' aria-labelledby="' . esc_attr( $uid ) . '-title"' : ' aria-label="' . esc_attr__( 'Contact form', 'wbd-real-estate-suite' ) . '"' ) . '>';
		$html .= '<input type="hidden" name="action" value="wbdres_inquiry" />';
		$html .= '<input type="hidden" name="type" value="' . esc_attr( $type ) . '" />';
		$html .= '<input type="hidden" name="property_id" value="' . esc_attr( $pid ) . '" />';
		$html .= '<input type="hidden" name="agent_id" value="' . esc_attr( $aid ) . '" />';
		$html .= '<input type="hidden" name="_ts" value="' . esc_attr( time() ) . '" />';
		$html .= '<div class="wbdres-hp" aria-hidden="true"><label for="' . esc_attr( $uid ) . '-website">' . esc_html__( 'Leave this field empty', 'wbd-real-estate-suite' ) . '</label><input type="text" id="' . esc_attr( $uid ) . '-website" name="website" tabindex="-1" autocomplete="off" /></div>';

		$html .= '<div class="wbdres-form__grid">';
		$html .= self::field(
			$uid,
			'name',
			__( 'Full name', 'wbd-real-estate-suite' ),
			'text',
			true,
			$user->exists() ? $user->display_name : '',
			array(
				'autocomplete' => 'name',
				'maxlength'    => 100,
			)
		);
		$html .= self::field(
			$uid,
			'email',
			__( 'Email', 'wbd-real-estate-suite' ),
			'email',
			true,
			$user->exists() ? $user->user_email : '',
			array(
				'autocomplete' => 'email',
				'maxlength'    => 191,
			)
		);
		if ( $a['showPhone'] || 'viewing' === $type ) {
			$html .= self::field(
				$uid,
				'phone',
				__( 'Phone', 'wbd-real-estate-suite' ),
				'tel',
				( $a['phoneReq'] || 'viewing' === $type ),
				'',
				array(
					'autocomplete' => 'tel',
					'maxlength'    => 30,
					'inputmode'    => 'tel',
				)
			);
		}
		if ( 'viewing' === $type ) {
			$html .= self::field( $uid, 'preferred_date', __( 'Preferred date', 'wbd-real-estate-suite' ), 'date', true, '', array( 'min' => gmdate( 'Y-m-d' ) ) );
			$html .= '<div class="wbdres-field"><label for="' . esc_attr( $uid ) . '-time">' . esc_html__( 'Preferred time', 'wbd-real-estate-suite' ) . '</label><select id="' . esc_attr( $uid ) . '-time" name="preferred_time">'
				. '<option value="morning">' . esc_html__( 'Morning', 'wbd-real-estate-suite' ) . '</option>'
				. '<option value="afternoon">' . esc_html__( 'Afternoon', 'wbd-real-estate-suite' ) . '</option>'
				. '<option value="evening">' . esc_html__( 'Evening', 'wbd-real-estate-suite' ) . '</option>'
				. '</select></div>';
		}
		$html .= '</div>';
		$html .= '<div class="wbdres-field wbdres-field--full"><label for="' . esc_attr( $uid ) . '-message">' . esc_html__( 'Message', 'wbd-real-estate-suite' ) . ( 'contact' === $type ? ' <span class="wbdres-req" aria-hidden="true">*</span>' : '' ) . '</label><textarea id="' . esc_attr( $uid ) . '-message" name="message" rows="4" maxlength="3000"' . ( 'contact' === $type ? ' required aria-required="true"' : '' ) . '>' . esc_textarea( $a['message'] ) . '</textarea></div>';

		if ( Settings::get( 'consent_required', 1 ) ) {
			$consent = (string) Settings::get( 'consent_text' );
			$page    = (int) Settings::get( 'privacy_page_id' );
			if ( ! $page ) {
				$page = (int) get_option( 'wp_page_for_privacy_policy' );
			}
			$policy = $page && 'publish' === get_post_status( $page ) ? ' <a href="' . esc_url( get_permalink( $page ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Privacy Policy', 'wbd-real-estate-suite' ) . '</a>' : '';
			$html  .= '<div class="wbdres-field wbdres-field--check"><input type="checkbox" id="' . esc_attr( $uid ) . '-consent" name="consent" value="1" required aria-required="true" /><label for="' . esc_attr( $uid ) . '-consent">' . wp_kses_post( $consent ) . $policy . ' <span class="wbdres-req" aria-hidden="true">*</span></label></div>';
		}

		$html .= '<div class="wbdres-form__status" role="status" aria-live="polite" data-wp-text="context.message" data-wp-class--is-error="state.isError" data-wp-class--is-success="state.isSuccess"></div>';
		$html .= '<button type="submit" class="wbdres-button wbdres-button--block" data-wp-bind--disabled="state.isSending">' . esc_html( $button ) . '</button>';
		$html .= '</form></div>';
		return $html;
	}

	/**
	 * A labelled input.
	 *
	 * @param string $uid      Form uid.
	 * @param string $name     Field name.
	 * @param string $label    Label.
	 * @param string $type     Input type.
	 * @param bool   $required Required.
	 * @param string $value    Value.
	 * @param array  $attrs    Extra attributes.
	 * @return string
	 */
	public static function field( $uid, $name, $label, $type, $required, $value = '', array $attrs = array() ) {
		$id    = $uid . '-' . $name;
		$html  = '<div class="wbdres-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . ( $required ? ' <span class="wbdres-req" aria-hidden="true">*</span>' : '' ) . '</label>';
		$html .= '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"';
		if ( $required ) {
			$html .= ' required aria-required="true"';
		}
		foreach ( $attrs as $k => $v ) {
			$html .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}
		return $html . ' /></div>';
	}

	/**
	 * Mortgage calculator.
	 *
	 * @param array $args price, rate, years, down, title.
	 * @return string
	 */
	public static function mortgage( array $args = array() ) {
		static $n = 0;
		++$n;
		$a = wp_parse_args(
			$args,
			array(
				'price' => 0,
				'rate'  => (float) Settings::get( 'mortgage_rate', 6.5 ),
				'years' => (int) Settings::get( 'mortgage_years', 30 ),
				'down'  => (float) Settings::get( 'mortgage_down', 20 ),
				'title' => __( 'Mortgage calculator', 'wbd-real-estate-suite' ),
			)
		);
		self::enqueue_interactivity();
		$uid    = 'wbdres-m' . $n;
		$price  = max( 0, (float) $a['price'] );
		$ctx    = array(
			'price'  => $price ? $price : 250000,
			'down'   => (float) $a['down'],
			'rate'   => (float) $a['rate'],
			'years'  => (int) $a['years'],
			'symbol' => (string) Settings::get( 'currency_symbol', '$' ),
			'pos'    => (string) Settings::get( 'currency_position', 'before' ),
			'locale' => str_replace( '_', '-', get_locale() ),
		);
		$fields = array(
			'price' => array( __( 'Property price', 'wbd-real-estate-suite' ), 0, null, 1000 ),
			'down'  => array( __( 'Down payment (%)', 'wbd-real-estate-suite' ), 0, 100, 1 ),
			'rate'  => array( __( 'Interest rate (%)', 'wbd-real-estate-suite' ), 0, 30, 0.05 ),
			'years' => array( __( 'Loan term (years)', 'wbd-real-estate-suite' ), 1, 50, 1 ),
		);

		$html = '<div class="wbdres-mortgage" data-wp-interactive="wbdres" ' . wp_interactivity_data_wp_context( $ctx ) . '>';
		if ( $a['title'] ) {
			$html .= '<h2 class="wbdres-mortgage__title">' . esc_html( $a['title'] ) . '</h2>';
		}
		$html .= '<div class="wbdres-mortgage__grid">';
		foreach ( $fields as $key => $f ) {
			$html .= '<div class="wbdres-field"><label for="' . esc_attr( $uid . '-' . $key ) . '">' . esc_html( $f[0] ) . '</label><input type="number" inputmode="decimal" id="' . esc_attr( $uid . '-' . $key ) . '" data-field="' . esc_attr( $key ) . '" value="' . esc_attr( $ctx[ $key ] ) . '" min="' . esc_attr( $f[1] ) . '"' . ( null !== $f[2] ? ' max="' . esc_attr( $f[2] ) . '"' : '' ) . ' step="' . esc_attr( $f[3] ) . '" data-wp-on--input="actions.updateMortgage" /></div>';
		}
		$html .= '</div><div class="wbdres-mortgage__result" aria-live="polite"><span>' . esc_html__( 'Estimated monthly payment', 'wbd-real-estate-suite' ) . '</span><strong data-wp-text="state.monthlyPayment">—</strong>'
			. '<small><span>' . esc_html__( 'Loan amount', 'wbd-real-estate-suite' ) . ':</span> <span data-wp-text="state.loanAmount"></span></small></div>';
		$html .= '<p class="wbdres-mortgage__note">' . esc_html__( 'Estimate only (principal and interest). Taxes, insurance and fees are not included.', 'wbd-real-estate-suite' ) . '</p>';
		return $html . '</div>';
	}

	/**
	 * Full single-property layout used for automatic templates and the Property Details block.
	 *
	 * @param Property $p       Property.
	 * @param string   $content Description HTML (already filtered).
	 * @param array    $show    Section toggles.
	 * @return string
	 */
	public static function single_layout( Property $p, $content, array $show = array() ) {
		$s = wp_parse_args(
			$show,
			array(
				'gallery'  => true,
				'header'   => true,
				'title'    => false,
				'specs'    => true,
				'features' => true,
				'video'    => true,
				'map'      => true,
				'agent'    => true,
				'inquiry'  => true,
				'viewing'  => true,
				'mortgage' => true,
				'similar'  => true,
				'cta'      => true,
			)
		);
		self::enqueue_interactivity();
		$agent = $p->agent();

		$html = '<div class="wbdres-single alignwide" data-wp-interactive="wbdres" ' . wp_interactivity_data_wp_context(
			array(
				'id'       => $p->id(),
				'shareMsg' => '',
			)
		) . ' data-wp-init="callbacks.trackView">';
		if ( $s['gallery'] ) {
			$html .= self::gallery( $p );
		}
		$html .= '<div class="wbdres-single__layout"><div class="wbdres-single__main">';
		if ( $s['header'] ) {
			$html .= self::header( $p, (bool) $s['title'] );
		}

		if ( $s['features'] ) {
			$features = self::features( $p );
			if ( $features ) {
				$html .= '<section class="wbdres-section wbdres-special" aria-labelledby="wbdres-h-feat"><h2 id="wbdres-h-feat" class="wbdres-section__title">' . esc_html__( "What's special", 'wbd-real-estate-suite' ) . '</h2>' . $features . '</section>';
			}
		}
		if ( trim( wp_strip_all_tags( $content ) ) || false !== strpos( $content, '<img' ) ) {
			$html .= '<section class="wbdres-section wbdres-description" aria-labelledby="wbdres-h-desc"><h2 id="wbdres-h-desc" class="wbdres-section__title">' . esc_html__( 'About this property', 'wbd-real-estate-suite' ) . '</h2><div class="wbdres-description__body">' . $content . '</div></section>';
		}
		if ( $s['specs'] ) {
			$specs = self::specs( $p, true, 'facts' );
			$ppa   = self::price_per_area( $p );
			if ( $specs ) {
				$html .= '<details class="wbdres-accordion" open><summary><h2 class="wbdres-section__title">' . esc_html__( 'Facts & features', 'wbd-real-estate-suite' ) . '</h2></summary><div class="wbdres-accordion__body">' . $specs
					. ( $ppa ? '<p class="wbdres-ppa">' . esc_html( $ppa ) . '</p>' : '' ) . '</div></details>';
			}
		}
		if ( $s['video'] ) {
			$video = self::video( $p );
			if ( $video ) {
				$html .= '<details class="wbdres-accordion" open><summary><h2 class="wbdres-section__title">' . esc_html__( 'Video & virtual tour', 'wbd-real-estate-suite' ) . '</h2></summary><div class="wbdres-accordion__body">' . $video . '</div></details>';
			}
		}
		if ( $s['map'] ) {
			$map = self::map( $p );
			if ( $map ) {
				$html .= '<details class="wbdres-accordion" open><summary><h2 class="wbdres-section__title">' . esc_html__( 'Location', 'wbd-real-estate-suite' ) . '</h2></summary><div class="wbdres-accordion__body">' . $map . '</div></details>';
			}
		}
		if ( $s['mortgage'] && ! $p->meta( 'price_on_request' ) && (float) $p->meta( 'price' ) > 0 && ! self::is_rental( $p ) ) {
			$html .= '<details class="wbdres-accordion" id="wbdres-mortgage"><summary><h2 class="wbdres-section__title">' . esc_html__( 'Payment calculator', 'wbd-real-estate-suite' ) . '</h2></summary><div class="wbdres-accordion__body">' . self::mortgage(
				array(
					'price' => (float) $p->meta( 'price' ),
					'title' => '',
				)
			) . '</div></details>';
		}
		$html .= '</div>';

		if ( $s['agent'] || $s['inquiry'] ) {
			$html .= '<aside class="wbdres-single__aside" id="wbdres-contact" aria-label="' . esc_attr__( 'Contact the agent', 'wbd-real-estate-suite' ) . '"><div class="wbdres-single__sticky wbdres-panel">';
			if ( $s['agent'] && $agent ) {
				$html .= '<p class="wbdres-panel__eyebrow">' . esc_html__( 'Listing agent', 'wbd-real-estate-suite' ) . '</p>';
				$html .= self::agent_card(
					$agent,
					array(
						'layout'  => 'compact',
						'heading' => 2,
						'social'  => false,
						'wa_text' => WhatsApp::property_message( $p ),
					)
				);
			} elseif ( $s['agent'] && WhatsApp::number_for() ) {
				// No agent assigned: still offer the site-wide WhatsApp number.
				$html .= '<div class="wbdres-agent__contact">' . WhatsApp::button( WhatsApp::number_for(), WhatsApp::property_message( $p ), __( 'Chat on WhatsApp', 'wbd-real-estate-suite' ) ) . '</div>';
			}
			if ( $s['inquiry'] ) {
				$html .= self::inquiry_form(
					array(
						'type'       => 'inquiry',
						'propertyId' => $p->id(),
						'agentId'    => $agent ? $agent->ID : 0,
						'title'      => $agent ? '' : __( 'Ask about this property', 'wbd-real-estate-suite' ),
						'compact'    => true,
					)
				);
			}
			if ( $s['viewing'] ) {
				$html .= '<details class="wbdres-viewing"><summary class="wbdres-button wbdres-button--outline wbdres-button--block">' . self::icon( 'calendar' ) . esc_html__( 'Schedule a viewing', 'wbd-real-estate-suite' ) . '</summary>'
					. self::inquiry_form(
						array(
							'type'       => 'viewing',
							'propertyId' => $p->id(),
							'agentId'    => $agent ? $agent->ID : 0,
							'compact'    => true,
						)
					) . '</details>';
			}
			$html .= '</div></aside>';
		}
		$html .= '</div>';

		if ( $s['similar'] ) {
			$similar = Listings::render(
				array(
					'similarTo'      => $p->id(),
					'perPage'        => 4,
					'columns'        => 4,
					'showPagination' => false,
					'showToolbar'    => false,
				)
			);
			if ( false === strpos( $similar, 'wbdres-empty' ) ) {
				$html .= '<section class="wbdres-section wbdres-similar" aria-labelledby="wbdres-h-similar"><h2 id="wbdres-h-similar" class="wbdres-section__title">' . esc_html__( 'Recommended for you', 'wbd-real-estate-suite' ) . '</h2>' . $similar . '</section>';
			}
		}

		if ( $s['cta'] && $s['inquiry'] ) {
			$html .= '<section class="wbdres-cta-band"><div><h2>' . esc_html__( 'Talk to an agent', 'wbd-real-estate-suite' ) . '</h2><p>' . esc_html__( 'Questions about this home, pricing or the neighbourhood? We reply quickly.', 'wbd-real-estate-suite' ) . '</p></div><a class="wbdres-button wbdres-button--light" href="#wbdres-contact">' . esc_html__( 'Send a message', 'wbd-real-estate-suite' ) . '</a></section>';
			$wa_num = WhatsApp::number_for( $agent );
			$wa_btn = $wa_num ? '<a class="wbdres-button wbdres-button--whatsapp wbdres-button--icon" href="' . esc_url( WhatsApp::url( $wa_num, WhatsApp::property_message( $p ) ) ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Chat on WhatsApp (opens WhatsApp)', 'wbd-real-estate-suite' ) . '">' . WhatsApp::icon() . '</a>' : '';
			$html  .= '<div class="wbdres-mobile-cta"><span class="wbdres-mobile-cta__price">' . self::price_html( $p ) . '</span><span class="wbdres-mobile-cta__actions">' . $wa_btn . '<a class="wbdres-button" href="#wbdres-contact">' . esc_html__( 'Contact agent', 'wbd-real-estate-suite' ) . '</a></span></div>';
		}
		return $html . '</div>';
	}

	/**
	 * Price per area unit, e.g. "$172 per sq ft".
	 *
	 * @param Property $p Property.
	 * @return string
	 */
	public static function price_per_area( Property $p ) {
		$price = (float) $p->meta( 'price' );
		$area  = (float) $p->meta( 'area' );
		if ( $price <= 0 || $area <= 0 || $p->meta( 'price_on_request' ) || self::is_rental( $p ) ) {
			return '';
		}
		$units = Settings::area_units();
		$unit  = Settings::get( 'area_unit', 'sqft' );
		/* translators: 1: price, 2: area unit. */
		return sprintf( __( '%1$s per %2$s', 'wbd-real-estate-suite' ), Format::price( round( $price / $area ) ), isset( $units[ $unit ] ) ? $units[ $unit ] : $unit );
	}

	/**
	 * Whether the property is a rental (status slug contains "rent").
	 *
	 * @param Property $p Property.
	 * @return bool
	 */
	public static function is_rental( Property $p ) {
		$status = $p->term( Registrar::STATUS );
		return $status && false !== strpos( $status->slug, 'rent' );
	}
}
