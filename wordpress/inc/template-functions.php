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

/**
 * Hardened markup for content-embedded iframes (Airtable forms on the
 * Inquiry/Contact pages, and any future embeds): an accessible title,
 * native lazy-loading and safer frame flags. Media embeds (YouTube etc.)
 * keep allowfullscreen so playback is unaffected.
 */
function apexvalue_harden_embed_iframes( $content ) {
	if ( is_admin() || is_feed() || empty( $content ) || false === stripos( $content, '<iframe' ) ) {
		return $content;
	}

	return preg_replace_callback(
		'/<iframe\b[^>]*>/i',
		function ( $m ) {
			$tag  = $m[0];
			$attr = strtolower( $tag );

			if ( false === strpos( $attr, 'title=' ) ) {
				$src   = ( preg_match( '/src=["\']([^"\']+)["\']/i', $tag, $s ) ) ? $s[1] : '';
				$title = 'Embedded content';
				if ( false !== stripos( $src, 'airtable' ) ) {
					$title = __( 'Online inquiry form', 'apexvalue' );
				} elseif ( preg_match( '/(youtube|vimeo|youtu\.be)/i', $src ) ) {
					$title = __( 'Embedded video', 'apexvalue' );
				}
				$tag = preg_replace( '/^<iframe/i', '<iframe title="' . esc_attr( $title ) . '"', $tag );
			}

			if ( false === strpos( $attr, 'loading=' ) ) {
				$tag = preg_replace( '/^<iframe/i', '<iframe loading="lazy"', $tag );
			}

			if ( false === stripos( $tag, 'referrerpolicy=' ) ) {
				$tag = preg_replace( '/\s*>$/', ' referrerpolicy="strict-origin-when-cross-origin">', $tag );
			}

			return $tag;
		},
		$content
	);
}
add_filter( 'the_content', 'apexvalue_harden_embed_iframes', 999 );
