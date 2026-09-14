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

/**
 * Drop frontend assets that are provably unused on this site.
 *
 * Evidence (audited 2026-09, all three verified against rendered guest
 * markup and the database before removal):
 *
 *  - dashicons (59 KB): enqueued unconditionally by Post Views Counter
 *    for its counter icon, but the counter is not displayed anywhere
 *    (PVC display option unset, zero [post_views] shortcode users, no
 *    dashicons- classes in any guest-facing markup).
 *  - post-views-counter-frontend (1 KB): ships with dashicons for the
 *    same never-displayed counter.
 *  - storefront-woocommerce-brands CSS + JS (~5 KB): Storefront loads
 *    these whenever the WC_Brands extension class exists (bundled with
 *    modern WooCommerce), but no brand taxonomy or terms exist on this
 *    site and no brand markup is rendered on product pages.
 *
 * All removals are dequeue-level (no plugin files touched) and safe to
 * delete here if a view counter or brand filter ever appears on the
 * site.
 */
function apexvalue_trim_unused_assets() {
	if ( is_admin() || is_customize_preview() ) {
		return;
	}

	foreach ( array( 'dashicons', 'post-views-counter-frontend', 'storefront-woocommerce-brands-style' ) as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}

	foreach ( array( 'storefront-woocommerce-brands' ) as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'apexvalue_trim_unused_assets', 100 );

/**
 * Defer the three head scripts that are not already deferred.
 *
 * WooCommerce's own head scripts (add-to-cart, blockUI, woocommerce,
 * js.cookie, cart-fragments) and the Google reviews widget arrive with
 * defer already; jQuery, jQuery Migrate and this theme's script do not.
 * Deferring them removes the last three render-blocking scripts.
 *
 * jQuery must never get the async attribute (plugins print inline code
 * that expects it synchronously), and defer is only applied when the
 * tag carries no existing async/defer — never double-annotate.
 *
 * @param string $tag    Script tag HTML.
 * @param string $handle Script handle.
 * @return string
 */
function apexvalue_defer_head_scripts( $tag, $handle ) {
	// 'jquery' is the alias handle; the tag is printed under 'jquery-core'.
	$defer = array( 'jquery', 'jquery-core', 'jquery-migrate', 'apexvalue-js' );

	if ( ! in_array( $handle, $defer, true ) ) {
		return $tag;
	}

	if ( false !== stripos( $tag, ' async' ) || false !== stripos( $tag, ' defer' ) ) {
		return $tag;
	}

	if ( is_admin() ) {
		return $tag;
	}

	return str_replace( ' src=', ' defer src=', $tag );
}
add_filter( 'script_loader_tag', 'apexvalue_defer_head_scripts', 10, 2 );

/**
 * Stop WordPress printing the emoji shims.
 *
 * The site's design system uses real SVG/CSS icons, not emoji glyphs, so
 * the inline converter blob, the emoji release detector script (~4.5 KB)
 * and the TinyMCE emoji stylesheet only cost bytes and two render-blocking
 * head requests. Also strips the preconnect/prefetch hints aimed at
 * s.w.org / twemoji's CDN.
 */
function apexvalue_drop_emoji_assets() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'apexvalue_drop_emoji_assets' );

/**
 * Remove the emoji-related DNS hints from wp_head.
 */
function apexvalue_drop_emoji_resource_hints( $hints, $relation ) {
	if ( 'dns-prefetch' !== $relation ) {
		return $hints;
	}

	return array_filter(
		$hints,
		function ( $hint ) {
			return false === strpos( (string) $hint, 's.w.org' )
				&& false === strpos( (string) $hint, 's0.wp.com' )
				&& false === strpos( (string) $hint, 'twemoji' );
		}
	);
}
add_filter( 'wp_resource_hints', 'apexvalue_drop_emoji_resource_hints', 10, 2 );

