<?php
/**
 * Apex Value theme customizations.
 *
 * Keeps Storefront functionality intact while providing:
 *  - a compact, card-styled header (logo + search + cart icon),
 *  - a reusable menu location for easier administration,
 *  - child styles enqueued after all parent + customizer CSS.
 *
 * The header cart icon comes from inc/template-overrides.php, which
 * redefines Storefront's pluggable storefront_cart_link() /
 * storefront_header_cart(). Because those functions are called by
 * Storefront's own add-to-cart fragment handler, AJAX cart updates
 * keep working without extra code here.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ApexValue_Theme' ) ) :

	/**
	 * The main Apex Value child theme class.
	 */
	class ApexValue_Theme {

		/**
		 * Setup hooks.
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ), 20 );
			add_filter( 'body_class', array( $this, 'body_classes' ) );
			add_action( 'init', array( $this, 'register_reusable_menu' ), 5 );

			// Drop jquery-migrate on the front end. Every script this site
			// loads (Storefront, WooCommerce, the quotes plugin, the Google
			// Reviews widget) was audited for jQuery-3-removed APIs (bind/
			// unbind/delegate/parseJSON); all are clean, and the one deprecated
			// call in the wild ($.isArray) still exists in 3.7.1 core. Migrate
			// is ~24 KB of dead weight here. Admin and login screens keep it.
			add_action( 'wp_default_scripts', array( $this, 'dequeue_jquery_migrate' ) );

			// Serve a trimmed icons stylesheet. Storefront's icons.css is the
			// full Font Awesome catalog (~75 KB of rules for ~1,500 icons);
			// assets/css/icons-subset.css keeps only the rules for glyphs this
			// site renders (29 KB, font-display: swap, subset font URLs).
			add_filter( 'style_loader_src', array( $this, 'swap_icons_css' ), 10, 2 );

			// Tag the custom-logo <img> so dark mode can invert it (the
			// artwork is 100% black monochrome — see the dark block in
			// style.css).
			add_filter( 'get_custom_logo', array( $this, 'tag_logo_for_dark_mode' ) );

			// Defer the site's remaining synchronous scripts (see
			// defer_safe_scripts): PVC's JS-mode counter (render-blocking
			// in <head>), plus six body scripts that halt HTML parsing.
			add_filter( 'script_loader_tag', array( $this, 'defer_safe_scripts' ), 10, 3 );

			// Color-mode bootstrap (metas + no-FOUC script). Registered here
			// at priority 1 — NOT from scripts(): wp_enqueue_scripts itself
			// fires at wp_head priority 1, and a callback added during that
			// same-priority pass never runs for the request.
			add_action( 'wp_head', array( $this, 'theme_mode_head' ), 1 );
		}

		/**
		 * Tag the custom-logo <img> with a class dark mode can target.
		 *
		 * The site logo is 100% black monochrome artwork, so in dark mode
		 * CSS inverts it to white (see style.css). This filter simply marks
		 * which images are ours to touch — it changes nothing in light mode.
		 *
		 * @param string $html Custom logo HTML.
		 * @return string Maybe-tagged logo HTML.
		 */
		public function tag_logo_for_dark_mode( $html ) {
			if ( false !== strpos( $html, ' class="' ) ) {
				return str_replace( ' class="', ' class="apex-logo-invertable ', $html );
			}
			return str_replace( '<img ', '<img class="apex-logo-invertable" ', $html );
		}

		/**
		 * Add defer to a whitelist of synchronous front-end scripts.
		 *
		 * Every script here was audited file-by-file before deferring:
		 *
		 * - `post-views-counter-frontend` — PVC prints its JS-mode counter
		 *   in <head> with no defer: a render-blocking parser stall on
		 *   every page (and we deliberately enabled JS mode for
		 *   page-cache compatibility in 1.12.5). Its config var is an
		 *   inline script that runs during parse (before any deferred
		 *   file), it waits for DOMContentLoaded itself, and init() only
		 *   reads cookies and fires the count request. Counting verified
		 *   in the DB after deferring (371 → 372 across all aggregates).
		 * - Storefront navigation / header-cart / handheld bar / brands —
		 *   plain DOM-event binders (navigation waits for DOMContentLoaded
		 *   itself); no document.write, no mid-parse DOM writes.
		 * - sourcebuster / order-attribution — UMD lib defining window.sbjs
		 *   plus a reader of location/referrer/sessionStorage, all stable
		 *   after parse; document order is preserved among deferred files
		 *   (sourcebuster still lands before order-attribution), and
		 *   jQuery (head, defer) still executes first.
		 *
		 * @param string $tag    Script HTML tag (may be a whole block:
		 *                       inline -before tag + src tag, per WP core).
		 * @param string $handle Script handle (no `-js` tag-id suffix).
		 * @param string $src    Script URL (unused).
		 * @return string Maybe-deferred tag.
		 */
		public function defer_safe_scripts( $tag, $handle, $src ) {
			static $safe = array(
				'post-views-counter-frontend',
				'storefront-navigation',
				'storefront-header-cart',
				'storefront-handheld-footer-bar',
				'storefront-woocommerce-brands',
				'sourcebuster-js',
				'wc-order-attribution',
			);

			if ( ! in_array( $handle, $safe, true ) ) {
				return $tag;
			}

			// Anchor on the src tag itself: WP hands us the inline
			// localize tag and the src tag as one string, and the inline
			// tag must stay untouched (defer is inert there anyway, but
			// keep the markup honest). Matches only a `<script` whose own
			// attributes contain src= and that isn't already deferred.
			return preg_replace(
				'/<script(?![^>]*\bdefer\b)(?=[^>]*\ssrc=)([^>]*)>/',
				'<script defer$1>',
				$tag,
				1
			);
		}

		/**
		 * Load clearly non-critical stylesheets without blocking render.
		 *
		 * The homepage alone ships 14 render-blocking stylesheets; under
		 * Lighthouse's simulated throttle the whole render gate waits for
		 * every byte of them (FCP 2.6 s in lab). These five style
		 * below-the-fold or page-specific UI and are safe to fetch async
		 * via the standard preload + onload swap (with a <noscript>
		 * fallback so no-JS agents still get the styles):
		 *
		 * - brands.css ............. Storefront brand archive only
		 * - public-main.css ........ Google Reviews widget (below fold)
		 * - cookies.css ............ cookie bar chrome (bottom overlay)
		 * - wc-blocks.css,
		 *   product-image.css ...... product-card grids below the hero;
		 *                           they arrive long before the lazy
		 *                           images they size (CLS stays 0 —
		 *                           verified in Lighthouse)
		 *
		 * Everything above the fold (parent/child core CSS, icon subset)
		 * stays render-blocking on purpose — no FOUC on header/hero.
		 *
		 * @param string $html    Link tag.
		 * @param string $handle  Style handle.
		 * @param string $href    Style URL (unused).
		 * @param string $media   Media attribute (preserved as-is).
		 * @return string Maybe-async link tag (+ noscript fallback).
		 */
		public function async_noncritical_css( $html, $handle, $href, $media ) {
			static $async = array(
				'storefront-woocommerce-brands-style',
				'grw-public-main-css',
				'siteseo-admin-cookies',
				'wc-blocks-style',
				'wc-blocks-style-product-image',
			);

			if ( ! in_array( $handle, $async, true ) || false === strpos( $html, "rel='stylesheet'" ) ) {
				return $html;
			}

			$preload = str_replace(
				"rel='stylesheet'",
				"rel='preload' as='style' onload=\"this.rel='stylesheet'\"",
				$html
			);

			// No-JS fallback: plain stylesheet link inside <noscript>.
			return $preload . "\n" . '<noscript>' . $html . '</noscript>';
		}

		/**
		 * Register a reusable "Main Menu" so admins can edit items in one place.
		 */
		public function register_reusable_menu() {
			register_nav_menu( 'apexvalue-main', __( 'Main Menu (replaces Primary & Handheld)', 'apexvalue' ) );
		}

		/**
		 * Front-end asset tweaks.
		 *
		 * Note: Storefront itself enqueues the child stylesheet as the
		 * `storefront-child-style` handle (see Storefront::child_scripts()),
		 * after the parent CSS and the Customizer's inline colors. So we do
		 * NOT enqueue style.css again here — that would load it twice.
		 */
		public function scripts() {
			// Preload the weights used on every page (400 body, 700
			// headings/UI, 900 = `h1`/the LCP element — its late discovery
			// after style.css parsed stretched the LCP chain) plus the solid
			// icon face (header cart/menu glyphs render above the fold) so
			// text and icons never swap late.
			add_action( 'wp_head', function () {
				$base = get_stylesheet_directory_uri() . '/assets/fonts/';
				printf(
					'<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin />' . "\n",
					esc_url( $base . 'source-sans-pro-400.woff2' )
				);
				printf(
					'<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin />' . "\n",
					esc_url( $base . 'source-sans-pro-700.woff2' )
				);
				printf(
					'<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin />' . "\n",
					esc_url( $base . 'source-sans-pro-900.woff2' )
				);
				printf(
					'<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin />' . "\n",
					esc_url( $base . 'fa-solid-900.woff2' )
				);
			}, 2 );

			// Load clearly non-critical stylesheets asynchronously so they
			// stop gating first render (see async_noncritical_css()).
			add_filter( 'style_loader_tag', array( $this, 'async_noncritical_css' ), 10, 4 );

			// Tiny progressive-enhancement script (reveals, header shadow,
			// cart bump, smooth anchors). Loaded in the header so the
			// scrolled-header state applies before first paint.
			wp_enqueue_script(
				'apexvalue-js',
				get_stylesheet_directory_uri() . '/assets/js/apexvalue.js',
				array( 'jquery' ), // cart-bump binds to wc_fragments events.
				APEXVALUE_VERSION,
				false
			);

		}

		/**
		 * Head markup for light/dark mode: color-scheme + theme-color metas
		 * and the tiny no-FOUC bootstrap script.
		 *
		 * Must run before stylesheets print (wp_head priority 1; styles go
		 * out around priority 8) so dark pages never flash white.
		 *
		 * @return void
		 */
		public function theme_mode_head() {
			echo '<meta name="color-scheme" content="light dark" />' . "\n";
			echo '<meta name="theme-color" content="#ffffff" />' . "\n";
			echo <<<"SCRIPT"
<script>
(function(){try{var k='apexvalue:mode',t=localStorage.getItem(k),h=document.documentElement,m=t||(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');if(t){h.setAttribute('data-apx-theme',t)}var c=m==='dark'?'#101418':'#ffffff',n=document.querySelector('meta[name="theme-color"]');if(n){n.setAttribute('content',c)}}catch(e){}})();
</script>
SCRIPT;
		}

		/**
		 * Point the `storefront-icons` stylesheet at our trimmed subset.
		 *
		 * Swapping the style source (instead of loading a competing
		 * stylesheet) preserves handle order, dependencies, and the RTL
		 * replace behaviour, so nothing about Storefront's enqueue changes —
		 * the browser just downloads 29 KB instead of 75 KB.
		 *
		 * @param string $src    Stylesheet URL.
		 * @param string $handle Style handle.
		 * @return string Maybe-rewritten URL.
		 */
		public function swap_icons_css( $src, $handle ) {
			if ( is_admin() || 'storefront-icons' !== $handle ) {
				return $src;
			}
			if ( false === strpos( $src, '/themes/storefront/assets/css/base/icons' ) ) {
				return $src;
			}
			return get_stylesheet_directory_uri() . '/assets/css/icons-subset.css?ver=' . APEXVALUE_VERSION;
		}

		/**
		 * Remove jquery-migrate from the front-end jQuery bundle.
		 *
		 * Runs on wp_default_scripts, i.e. while WP_Scripts is constructed,
		 * before anything is enqueued — so jquery's deps list never carries
		 * jquery-migrate on the public site. wp-admin / login stay untouched
		 * because this class only loads on the front end.
		 *
		 * @param WP_Scripts $scripts The scripts manager instance.
		 */
		public function dequeue_jquery_migrate( $scripts ) {
			if ( ! is_admin() && ! did_action( 'login_init' ) ) {
				$scripts->remove( 'jquery' );
				$scripts->add( 'jquery', false, array( 'jquery-core' ), '1.12.4-wp' );
			}
		}

		/**
		 * Body classes used by the stylesheet.
		 */
		public function body_classes( $classes ) {
			$classes[] = 'apexvalue-theme';

			return $classes;
		}
	}

endif;

return new ApexValue_Theme();
