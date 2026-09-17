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
			// headings/UI) so text doesn't swap late on first paint.
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

			// Subset icon fonts. Storefront's icons.css ships @font-face rules
			// for the full ~150 KB Font Awesome files; this site renders only
			// 52 glyphs (theme icons + Storefront's component icons). Printing
			// a second @font-face after it re-points both families at 4 KB
			// subsets in assets/fonts/ — later declarations win the cascade.
			wp_register_style( 'apexvalue-font-fa', false, array( 'storefront-icons' ), APEXVALUE_VERSION );
			wp_enqueue_style( 'apexvalue-font-fa' );
			wp_add_inline_style(
				'apexvalue-font-fa',
				sprintf(
					'@font-face{font-family:"Font Awesome 5 Free";font-style:normal;font-weight:900;font-display:block;src:url(%1$s) format("woff2")}' .
					'@font-face{font-family:"Font Awesome 5 Brands";font-style:normal;font-weight:400;font-display:block;src:url(%2$s) format("woff2")}',
					wp_json_encode( get_stylesheet_directory_uri() . '/assets/fonts/fa-solid-900.woff2' ),
					wp_json_encode( get_stylesheet_directory_uri() . '/assets/fonts/fa-brands-400.woff2' )
				)
			);
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
