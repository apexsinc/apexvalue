<?php
/**
 * Pluggable Storefront function overrides.
 *
 * This file is required from the child theme's functions.php at load time.
 * WordPress loads a child theme's functions.php BEFORE the parent's, so the
 * function_exists() guards in Storefront skip their own definitions and our
 * versions below win. This is the safest override mechanism — no hooks are
 * removed and WooCommerce behaviour is unchanged.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'storefront_cart_link' ) ) {
	/**
	 * Cart icon link for the header.
	 *
	 * Replaces Storefront's "subtotal + N items" text link with a compact
	 * icon + count chip (styled in style.css). Fragment-safe: see
	 * ApexValue_Theme::cart_fragments().
	 */
	function storefront_cart_link() {
		if ( function_exists( 'apexvalue_cart_link' ) ) {
			apexvalue_cart_link();
		}
	}
}

if ( ! function_exists( 'apexvalue_cart_link' ) ) {
	/**
	 * The actual cart icon markup, reusable for fragments.
	 */
	function apexvalue_cart_link() {
		if ( function_exists( 'storefront_woo_cart_available' ) && ! storefront_woo_cart_available() ) {
			return;
		}
		?>
		<a class="cart-contents" href="<?php echo esc_url( wc_get_cart_url() ); ?>" title="<?php esc_attr_e( 'View your shopping cart', 'apexvalue' ); ?>">
			<span class="screen-reader-text">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of items in cart */
						_n( '%d item in cart', '%d items in cart', WC()->cart->get_cart_contents_count(), 'apexvalue' ),
						WC()->cart->get_cart_contents_count()
					)
				);
				?>
			</span>
			<span class="count" aria-hidden="true"><?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?></span>
		</a>
		<?php
	}
}

/**
 * Give SiteSEO's full-site search loop the product-card treatment.
 *
 * The product-specific search (`?s=...&post_type=product`) uses the standard
 * WooCommerce `ul.products` grid, which our design system already covers.
 * SiteSEO's general search renders each result as a plain WordPress
 * `<article class="... type-product">`, so product hits would show as flat,
 * unstyled entries next to styled blog hits.
 *
 * We append the same card classes our loop rules use (kept in sync with
 * section 5 of style.css). If WooCommerce is inactive the filter is a no-op.
 *
 * @param array $classes Post classes.
 * @return array
 */
function apexvalue_product_search_card_classes( $classes ) {
	// NOTE: is_woocommerce() is deliberately NOT used here — it is false on
	// search results pages. The `type-product` class is the reliable signal.
	if ( ! is_search() || ! class_exists( 'WooCommerce' ) ) {
		return $classes;
	}

	if ( in_array( 'type-product', (array) $classes, true ) ) {
		$classes[] = 'apex-card';
		$classes[] = 'apex-card--product';
	}

	return $classes;
}
add_filter( 'post_class', 'apexvalue_product_search_card_classes', 20 );

if ( ! function_exists( 'storefront_header_cart' ) ) {
	/**
	 * Header cart: icon trigger + accessible dropdown mini-cart.
	 *
	 * Markup mirrors Storefront (ul.site-header-cart with two li) so
	 * Storefront/WooCommerce CSS and JS keep working.
	 */
	function storefront_header_cart() {
		if ( ! function_exists( 'storefront_is_woocommerce_activated' ) || ! storefront_is_woocommerce_activated() ) {
			return;
		}

		$class = is_cart() ? ' current-menu-item' : '';
		?>
		<ul id="site-header-cart" class="site-header-cart menu<?php echo esc_attr( $class ); ?>">
			<li class="apexvalue-cart-trigger">
				<?php storefront_cart_link(); ?>
			</li>
			<li class="apexvalue-cart-dropdown">
				<?php the_widget( 'WC_Widget_Cart', 'title=' ); ?>
			</li>
		</ul>
		<?php
	}
}

/**
 * Legacy URL redirect: /contact/ → /contact-us/ (the live contact page).
 * 301 so search engines and stale bookmarks resolve to the real page.
 *
 * @return void
 */
function apexvalue_legacy_contact_redirect() {
	if ( is_404() && '/contact/' === trailingslashit( strtok( $_SERVER['REQUEST_URI'] ?? '', '?' ) ) ) {
		wp_safe_redirect( home_url( '/contact-us/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'apexvalue_legacy_contact_redirect' );
