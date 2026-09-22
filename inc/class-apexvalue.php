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

			// Post Views Counter prints its JS-mode counter in <head> with no
			// defer — a render-blocking parser stall on every page (and we
			// deliberately enabled JS mode for page-cache compatibility).
			// It is safe to defer: its config var is an inline script that
			// runs during parse (before any deferred file), it waits for
			// DOMContentLoaded itself, and init() touches no DOM nodes.
			add_filter( 'script_loader_tag', array( $this, 'defer_pvc_counter' ), 10, 3 );

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
		 * Add defer to Post Views Counter's front-end script.
		 *
		 * PVC enqueues frontend.js in <head> without a defer strategy, so
		 * the browser blocks HTML parsing to fetch+eval this 4 KB ping
		 * script on every view. Deferring it:
		 *   - keeps pvcArgsFrontend correct (the inline localize script
		 *     runs during parse, before any deferred file executes),
		 *   - keeps counting correct (the script waits for DOMContentLoaded
		 *     itself when still parsing; init() only reads cookies and
		 *     fires the count request).
		 * Verified live after v1.13.2: views still increment through the
		 * page cache in JS mode.
		 *
		 * @param string $tag    Script HTML tag.
		 * @param string $handle Script handle.
		 * @param string $src    Script URL (unused).
		 * @return string Maybe-deferred tag.
		 */
		public function defer_pvc_counter( $tag, $handle, $src ) {
			// Note: WP passes the whole *block* — the inline `-before`
			// localize script plus the src script — as one $tag, and the
			// handle carries no `-js` suffix (that is only the tag id).
			// So anchor on the src tag itself: match `<script` that has
			// src= before the first `>` and isn't already deferred, and
			// touch nothing else in the block.
			if ( 'post-views-counter-frontend' === $handle ) {
				$tag = preg_replace(
					'/<script(?![^>]*\bdefer\b)(?=[^>]*\ssrc=)([^>]*)>/',
					'<script defer$1>',
					$tag,
					1
				);
			}
			return $tag;
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
			// Preload the two weights used on every page (400 body, 700
			// headings/UI) plus the solid icon face (header cart/menu glyphs
			// render above the fold) so text and icons don't swap late.
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
					esc_url( $base . 'fa-solid-900.woff2' )
				);
			}, 2 );

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
