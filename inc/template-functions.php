<?php
/**
 * Extra template-level tweaks for Apex Value.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Move Cart & Checkout out of the primary nav menu.
 *
 * The header now has a dedicated cart icon (with a mini-cart dropdown),
 * so those links only duplicated it. Approved change: the desktop nav
 * becomes Home / About / Solutions / Shop; Cart and Checkout remain
 * reachable via the cart icon and the mobile bottom bar.
 *
 * Note: filters only affect display. Nothing is deleted from the menu.
 */
add_filter( 'wp_nav_menu_objects', 'apexvalue_filter_header_menu', 12, 2 );

/**
 * Filter the header menus to hide Cart/Checkout items.
 *
 * @param array    $items Sorted nav menu items.
 * @param stdClass $args  wp_nav_menu() arguments.
 * @return array
 */
function apexvalue_filter_header_menu( $items, $args ) {
	// Only touch the header menus rendered by Storefront.
	$is_header = in_array(
		$args->theme_location ?? '',
		array( 'primary', 'handheld', 'apexvalue-main' ),
		true
	);

	if ( ! $is_header ) {
		return $items;
	}

	$hidden_slugs = array( 'cart', 'checkout' );

	foreach ( $items as $key => $item ) {
		$is_cart_or_checkout = false;

		if ( 'post_type' === $item->type && 'page' === $item->object && function_exists( 'wc_get_page_id' ) ) {
			if ( in_array( (int) $item->object_id, array( wc_get_page_id( 'cart' ), wc_get_page_id( 'checkout' ) ), true ) ) {
				$is_cart_or_checkout = true;
			}
		}

		// Custom links ending in /cart/ or /checkout/ as a fallback.
		if ( 'custom' === $item->type ) {
			$path = trim( wp_parse_url( $item->url, PHP_URL_PATH ) ?? '', '/' );
			if ( in_array( $path, $hidden_slugs, true ) ) {
				$is_cart_or_checkout = true;
			}
		}

		if ( $is_cart_or_checkout ) {
			unset( $items[ $key ] );
		}
	}

	return $items;
}
