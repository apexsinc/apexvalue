<?php
/**
 * Image performance tweaks for Apex Value.
 *
 * WordPress 7.x already handles decoding="async", lazy-loading and
 * fetchpriority for most images. These filters close the two gaps that
 * remain on this site:
 *
 *  1. The single-product gallery main image is the LCP element but is
 *     lazy-loaded by WooCommerce's gallery markup — flip it to eager +
 *     fetchpriority="high".
 *  2. Block-gallery images beyond WP's skip-first-N limit (the three
 *     "Shop by collection" images on the homepage) still render eager —
 *     lazy-load everything after the first content image.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Make the main single-product gallery image the prioritized LCP image.
 *
 * The gallery main image carries the `wp-post-image` class and is the
 * first such image in the DOM (the summary/gallery section precedes the
 * related products loop), so the first hit wins and everything else is
 * left untouched.
 *
 * @param array  $attr       Image attributes.
 * @param object $attachment Attachment object.
 * @param string $size       Requested size.
 * @return array
 */
function apexvalue_gallery_lcp_image( $attr, $attachment, $size ) {
	if ( ! is_singular( 'product' ) || ! in_the_loop() ) {
		return $attr;
	}

	static $done = false;

	if ( $done ) {
		return $attr;
	}

	if ( empty( $attr['class'] ) || false === strpos( (string) $attr['class'], 'wp-post-image' ) ) {
		return $attr;
	}

	$attr['loading']       = 'eager';
	$attr['fetchpriority'] = 'high';
	$done                  = true;

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'apexvalue_gallery_lcp_image', 20, 3 );

/**
 * Lazy-load content images beyond the first one.
 *
 * WP core skips the first few content images when adding loading="lazy",
 * which leaves below-the-fold pattern images eager on the homepage. This
 * keeps the very first image eager (safety margin for small viewports)
 * and lazy-loads the rest.
 *
 * @param string $content Post content.
 * @return string
 */
function apexvalue_lazy_content_images( $content ) {
	if ( is_admin() || is_feed() || wp_doing_ajax() ) {
		return $content;
	}

	if ( ! is_string( $content ) || false === stripos( $content, '<img' ) ) {
		return $content;
	}

	$count = 0;

	return preg_replace_callback(
		'/<img[^>]*>/i',
		function ( $matches ) use ( &$count ) {
			$img = $matches[0];
			$count++;

			// First content image stays eager.
			if ( 1 === $count ) {
				return $img;
			}

			// Respect existing loading hints.
			if ( false !== stripos( $img, ' loading=' ) ) {
				return $img;
			}

			return str_replace( '<img', '<img loading="lazy"', $img );
		},
		$content
	);
}
add_filter( 'the_content', 'apexvalue_lazy_content_images', 50 );

/**
 * Fonts are self-hosted in the child stylesheet (assets/fonts/), so the
 * render-blocking Google Fonts request is no longer needed. (The block
 * editor in wp-admin keeps Storefront's editor styles, including its font
 * request — admin-only, doesn't affect front-end performance.)
 */
function apexvalue_dequeue_google_fonts() {
	wp_dequeue_style( 'storefront-fonts' );
	wp_deregister_style( 'storefront-fonts' );
}
add_action( 'wp_enqueue_scripts', 'apexvalue_dequeue_google_fonts', 25 );

