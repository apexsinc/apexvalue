<?php
/**
 * Request-a-Quotation flow for Apex Value.
 *
 * The site sells via quotation (Quotes for WooCommerce): products carry
 * "Request Quote" buttons, the cart is the quote basket, and checkout is
 * submitted through the "Ask for Quotation" gateway. This module puts the
 * quotation action everywhere customers expect it:
 *
 *  - a pill "Request a Quote" CTA in the desktop header,
 *  - a Quote item in the mobile handheld footer bar,
 *  - a "Quotation" badge where an empty price would leave a blank,
 *  - a short reassurance note above the quote basket.
 *
 * Everything is editable in Appearance → Customize → Apex Value Quote CTA.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The quote CTA link URL (Customizer-editable, sensible default).
 *
 * @return string
 */
function apexvalue_quote_url() {
	return get_theme_mod( 'apexvalue_quote_cta_url', home_url( '/inquiry/' ) );
}

/**
 * Register Customizer settings for the quotation CTA.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function apexvalue_quote_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'apexvalue_quote',
		array(
			'title'       => __( 'Apex Value Quote CTA', 'apexvalue' ),
			'priority'    => 36,
			'description' => __( 'The "Request a Quote" button in the header and mobile bar. Empty the label to hide the header button.', 'apexvalue' ),
		)
	);

	$wp_customize->add_setting(
		'apexvalue_quote_cta_text',
		array(
			'default'           => __( 'Request a Quote', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'apexvalue_quote_cta_text',
		array(
			'label'   => __( 'Button label', 'apexvalue' ),
			'section' => 'apexvalue_quote',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_quote_cta_url',
		array(
			'default'           => home_url( '/inquiry/' ),
			'sanitize_callback' => 'esc_url_raw',
		)
	);

	$wp_customize->add_control(
		'apexvalue_quote_cta_url',
		array(
			'label'       => __( 'Button link', 'apexvalue' ),
			'description' => __( 'Where should "Request a Quote" send visitors? Usually your inquiry form page.', 'apexvalue' ),
			'section'     => 'apexvalue_quote',
			'type'        => 'url',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_quote_badge_text',
		array(
			'default'           => __( 'Quotation', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'apexvalue_quote_badge_text',
		array(
			'label'       => __( 'Price badge label', 'apexvalue' ),
			'description' => __( 'Shown on products instead of an empty price. Empty to show nothing.', 'apexvalue' ),
			'section'     => 'apexvalue_quote',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_quote_cta_mobile',
		array(
			'default'           => 'yes',
			'sanitize_callback' => 'wp_validate_boolean',
		)
	);

	$wp_customize->add_control(
		'apexvalue_quote_cta_mobile',
		array(
			'label'   => __( 'Add Quote link to the mobile bottom bar', 'apexvalue' ),
			'section' => 'apexvalue_quote',
			'type'    => 'checkbox',
		)
	);
}
add_action( 'customize_register', 'apexvalue_quote_customize_register', 20 );

/**
 * Render the header "Request a Quote" pill (desktop).
 *
 * Hidden on the quote basket/checkout — the page itself is the action there.
 */
function apexvalue_quote_cta_render() {
	if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() ) ) {
		return;
	}

	$text = get_theme_mod( 'apexvalue_quote_cta_text', __( 'Request a Quote', 'apexvalue' ) );
	$url  = apexvalue_quote_url();

	if ( '' === trim( (string) $text ) || '' === trim( (string) $url ) ) {
		return;
	}

	printf(
		'<a class="apex-quote-cta" href="%1$s">%2$s</a>',
		esc_url( $url ),
		esc_html( $text )
	);
}
add_action( 'storefront_header', 'apexvalue_quote_cta_render', 62 );

/**
 * Add a Quote item to the handheld footer bar (leftmost position).
 *
 * @param array $links Handheld bar links (key => priority/callback).
 * @return array
 */
function apexvalue_handheld_quote_link( $links ) {
	if ( ! get_theme_mod( 'apexvalue_quote_cta_mobile', true ) ) {
		return $links;
	}

	$url = apexvalue_quote_url();
	if ( '' === trim( (string) $url ) ) {
		return $links;
	}

	$quote = array(
		'quote'    => array(
			'priority' => 0,
			'callback' => 'apexvalue_handheld_quote_callback',
		),
	);

	return $quote + $links;
}
add_filter( 'storefront_handheld_footer_bar_links', 'apexvalue_handheld_quote_link', 20 );

/**
 * Handheld bar quote link callback.
 */
function apexvalue_handheld_quote_callback() {
	printf(
		'<a href="%1$s">%2$s</a>',
		esc_url( apexvalue_quote_url() ),
		esc_html__( 'Request a Quote', 'apexvalue' )
	);
}

/**
 * Replace empty prices with a clean "Quotation" badge.
 *
 * Quote-only products (all of them, via Quotes for WooCommerce) render an
 * empty <p class="price"> which leaves an awkward blank under product
 * titles. A badge keeps the rhythm and tells the customer how buying works.
 *
 * @param string     $price_html Price HTML.
 * @param WC_Product $product    Product object.
 * @return string
 */
function apexvalue_quote_price_html( $price_html, $product ) {
	// Only when the price renders genuinely empty (quote-only products).
	if ( '' === trim( wp_strip_all_tags( (string) $price_html ) ) ) {
		$label = get_theme_mod( 'apexvalue_quote_badge_text', __( 'Quotation', 'apexvalue' ) );

		if ( '' === trim( (string) $label ) ) {
			return $price_html;
		}

		return '<span class="apex-quote-badge">' . esc_html( $label ) . '</span>';
	}

	return $price_html;
}
add_filter( 'woocommerce_get_price_html', 'apexvalue_quote_price_html', 20, 2 );

/**
 * Reassurance note above the quote basket / quote checkout.
 *
 * Shows on the cart page when it has items, and on checkout (where the
 * "Ask for Quotation" gateway collects the request details).
 */
function apexvalue_quote_cart_note() {
	$is_quote_checkout = function_exists( 'is_checkout' ) && is_checkout();
	$is_quote_cart     = function_exists( 'is_cart' ) && is_cart();

	if ( ! $is_quote_checkout && ! $is_quote_cart ) {
		return;
	}

	if ( $is_quote_cart && function_exists( 'WC' ) && WC()->cart && WC()->cart->is_empty() ) {
		return;
	}

	$message = $is_quote_checkout
		? __( 'No payment is taken here — submitting this order sends your quotation request to our team, who will reply with a formal quotation.', 'apexvalue' )
		: __( 'This is your quotation basket — submit it and our team will reply with a formal quotation, typically within one business day.', 'apexvalue' );

	printf(
		'<div class="apex-quote-note" role="note"><p>%s</p></div>',
		esc_html( $message )
	);
}
add_action( 'storefront_before_content', 'apexvalue_quote_cart_note', 30 );


/**
 * Publish the Customizer quote-badge label as a CSS custom property.
 *
 * Lets the empty quote-basket badge (pure CSS ::after in style.css) reuse
 * the same editable label as product-card badges without duplicating markup.
 */
function apexvalue_quote_css_vars() {
	$label = get_theme_mod( 'apexvalue_quote_badge_text', __( 'Quotation', 'apexvalue' ) );

	if ( '' === trim( (string) $label ) ) {
		$label = __( 'Quotation', 'apexvalue' );
	}

	printf(
		'<style id="apexvalue-quote-vars">:root{--apx-quote-badge:"%1$s";}</style>',
		esc_js( $label )
	);
}
add_action( 'wp_head', 'apexvalue_quote_css_vars', 20 );

/**
 * Give the empty quote basket a clear next step.
 *
 * The Cart block's empty state ends with a "New in store" grid but no
 * explicit path back to the catalog. A themed "Browse products" button
 * (core buttons block — inherits the design system, zero extra CSS)
 * closes that gap. Rendered server-side, so it needs no JavaScript.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Block name and attributes.
 * @return string
 */
function apexvalue_empty_cart_cta( $block_content, $block ) {
	if ( empty( $block['blockName'] ) || 'woocommerce/empty-cart-block' !== $block['blockName'] ) {
		return $block_content;
	}

	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';

	if ( '' === trim( (string) $shop_url ) ) {
		return $block_content;
	}

	$button = sprintf(
		'<!-- wp:buttons --><div class="wp-block-buttons" style="justify-content:center"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link" href="%1$s">%2$s</a></div><!-- /wp:button --></div><!-- /wp:buttons -->',
		esc_url( $shop_url ),
		esc_html__( 'Browse products', 'apexvalue' )
	);

	return $block_content . $button;
}
add_filter( 'render_block', 'apexvalue_empty_cart_cta', 10, 2 );
