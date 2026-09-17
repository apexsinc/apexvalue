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
 * Runs on BOTH the_content and render_block: block-rendered page content
 * never passes through the_content, so images hand-placed in page HTML
 * (e.g. the homepage pattern images) were missed entirely. The counter is
 * request-global so the exemption covers the first image whichever path
 * renders it; images that already carry a loading hint are counted but
 * left untouched, keeping double processing idempotent.
 *
 * @param string $content Post content or rendered block HTML.
 * @return string
 */
function apexvalue_lazy_content_images( $content ) {
	if ( is_admin() || is_feed() || wp_doing_ajax() ) {
		return $content;
	}

	if ( ! is_string( $content ) || false === stripos( $content, '<img' ) ) {
		return $content;
	}

	static $count = 0;

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
add_filter( 'render_block', 'apexvalue_lazy_content_images', 999 );

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
 *
 * NOTE: Storefront's WooCommerce Brands CSS/JS were removed in v1.10.0
 * on the belief that no brand taxonomy existed — that check had failed
 * silently and was wrong: a `davis-instruments` brand (324 products) is
 * in active use. The brands assets were therefore restored in v1.10.1
 * and must stay enqueued while any product carries a brand term.
 *
 * Removals are dequeue-level (no plugin files touched) and safe to
 * delete here if the view counter is ever displayed on the site.
 */
function apexvalue_trim_unused_assets() {
	if ( is_admin() || is_customize_preview() ) {
		return;
	}

	foreach ( array( 'dashicons', 'post-views-counter-frontend' ) as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
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

	// qwc-product-js (quotes-for-woocommerce) uses jQuery but registers with
	// empty deps, so the dependency check below cannot catch it.
	$defer[] = 'qwc-product-js';

	// Every script that depends on jQuery must be deferred too: jQuery itself
	// is deferred, deferred scripts execute in document order, and a
	// non-deferred tag placed after jQuery executes during parsing — before
	// deferred jQuery — with "jQuery is not defined" (seen with the siteseo
	// consent bar and the quotes plugin's product-page script).
	if ( ! in_array( $handle, $defer, true ) ) {
		$dep_obj = wp_scripts()->query( $handle );
		if ( $dep_obj && ! empty( $dep_obj->deps ) && array_intersect( $dep_obj->deps, array( 'jquery', 'jquery-core' ) ) ) {
			$defer[] = $handle;
		}
	}

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

/**
 * Strip the Gutenberg editor stack and password meter from storefront pages.
 *
 * Evidence (audited 2026-09-15):
 *
 *  - WooCommerce's cart/checkout block asset files declare `wp-plugins`
 *    and `wp-components` as dependencies (cart.asset.php, checkout.asset.php,
 *    cart-frontend.asset.php, checkout-frontend.asset.php, …). The shipped
 *    frontend bundles never reference them — zero `wp.components` /
 *    `wp.plugins` usages in wc-cart-checkout-base-frontend.js and
 *    wc-cart-block-frontend.js, and no Store API payment-extension plugin
 *    is in use. That stale dependency chain drags ~33 editor packages
 *    (components.min.js alone is 817 KB; the whole set ~1.3 MB) onto the
 *    cart page, which is otherwise the site's heaviest real page.
 *
 *  - `zxcvbn-async` (803 KB) plus the password-strength meters leak out of
 *    the Checkout block for logged-out visitors. This site has account
 *    registration disabled (`woocommerce_enable_myaccount_registration`
 *    and `woocommerce_registration_generate_password` are both "no"),
 *    guest checkout is on, and no registration form exists on the front
 *    end — there is nothing for the strength meter to attach to.
 *
 * The handles are dequeued AND scrubbed from every registered script's
 * dependency list, so queued parents (wc-blocks-checkout,
 * wc-cart-checkout-base, …) cannot re-pull them while resolving deps.
 *
 * SCOPE NOTE (tested 2026-09-15): only the password-meter chain is removed.
 * The editor packages (`wp-components`, `wp-plugins`) that WooCommerce's
 * cart/checkout block assets declare were ALSO tested for removal and are
 * genuinely required: the frontend bundles access `wp.components.SVG` and
 * `wp.plugins.PluginArea` at runtime (via externals invisible to static
 * grep). Do not widen this list without a full cart/checkout hydration
 * test in a real browser.
 *
 * IF ACCOUNT REGISTRATION IS EVER ENABLED, DELETE THIS FUNCTION (and
 * re-measure) — the meter becomes required again.
 *
 * Removals are dequeue-level (no plugin files touched).
 */
function apexvalue_trim_editor_stack_scripts() {
	if ( is_admin() || is_customize_preview() ) {
		return;
	}

	$drop = array(
		'zxcvbn-async',
		'password-strength-meter',
		'wc-password-strength-meter',
	);

	$wp_scripts = wp_scripts();

	foreach ( $drop as $handle ) {
		wp_dequeue_script( $handle );

		// Scrub from every registered script's dependency list.
		foreach ( $wp_scripts->registered as $dep_obj ) {
			if ( ! empty( $dep_obj->deps ) && is_array( $dep_obj->deps ) ) {
				$dep_obj->deps = array_diff( $dep_obj->deps, array( $handle ) );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'apexvalue_trim_editor_stack_scripts', 100 );

// Second pass: WooCommerce registers its cart/checkout block bundles during
// content rendering (after wp_enqueue_scripts) and they print in the footer.
// wp_print_footer_scripts runs at wp_footer priority 20 — this must be earlier.
add_action( 'wp_footer', 'apexvalue_trim_editor_stack_scripts', 10 );

