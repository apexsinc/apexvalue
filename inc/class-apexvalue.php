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
