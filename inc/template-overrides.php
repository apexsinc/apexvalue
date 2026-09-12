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
