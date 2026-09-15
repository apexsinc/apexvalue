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

/**
 * Remove WooCommerce's "Process your orders on the go. Get the app." block
 * from order emails.
 *
 * WC_Email_New_Order hooks its mobile-app promo into woocommerce_email_footer
 * at priority 9 (before the footer itself). The template it prints
 * (emails/email-mobile-messaging.php) is theme-overridable, but the block is
 * not wanted at all on this store — APEX Value does not advertise the
 * WooCommerce app. Unhooking here (priority 8) removes it for every email.
 */
function apexvalue_remove_email_app_promo() {
	if ( ! class_exists( 'WC_Email_New_Order' ) ) {
		return;
	}

	$mailer = WC()->mailer();
	if ( ! $mailer || ! isset( $mailer->emails['WC_Email_New_Order'] ) ) {
		return;
	}

	$email = $mailer->emails['WC_Email_New_Order'];
	if ( is_object( $email ) && has_action( 'woocommerce_email_footer', array( $email, 'mobile_messaging' ) ) ) {
		remove_action( 'woocommerce_email_footer', array( $email, 'mobile_messaging' ), 9 );
	}
}
add_action( 'woocommerce_email_footer', 'apexvalue_remove_email_app_promo', 8 );

/**
 * Strip money rows from every totals table a quote order renders.
 *
 * APEX Value sells via quotation: customers never see prices until an
 * official quote is sent outside the store. WooCommerce builds the
 * Subtotal/Total rows for the thank-you page, My Account order views and
 * the plain-text order emails from one place — get_order_item_totals() —
 * which is filterable. For orders carrying the quote plugin's
 * `_quote_status` meta the rows are removed entirely, so those surfaces
 * show only products and quantities. Real (non-quote) orders are
 * unaffected, and future pricing display only needs the meta removed.
 *
 * @param array  $total_rows Totals rows (label/value pairs).
 * @param WC_Order $order    The order being rendered.
 * @return array
 */
function apexvalue_hide_quote_totals( $total_rows, $order ) {
	if ( $order instanceof WC_Order && (bool) $order->get_meta( '_quote_status', true ) ) {
		return array();
	}
	return $total_rows;
}
add_filter( 'woocommerce_get_order_item_totals', 'apexvalue_hide_quote_totals', 10, 2 );

/**
 * Force the theme's thankyou template for quote orders.
 *
 * The order-received page must never show a Total row for quotation
 * orders. WooCommerce resolves checkout/thankyou.php through a chain of
 * template filters; on this site the resolved path does not always match
 * wc_locate_template()'s answer (an active plugin rewrites it), so the
 * theme override in woocommerce/checkout/ is not reliably included.
 * Forcing the path here — after every other filter has run — is the
 * last word before the template is included.
 *
 * @param string $template      Resolved template path.
 * @param string $template_name Template name (e.g. checkout/thankyou.php).
 * @param array  $args          Template args; contains the order.
 * @return string
 */
function apexvalue_force_quote_thankyou_template( $template, $template_name, $args ) {
	if ( 'checkout/thankyou.php' !== $template_name ) {
		return $template;
	}

	$order = $args['order'] ?? null;

	if ( $order instanceof WC_Order && (bool) $order->get_meta( '_quote_status', true ) ) {
		$mine = get_stylesheet_directory() . '/woocommerce/checkout/thankyou.php';
		if ( file_exists( $mine ) ) {
			return $mine;
		}
	}

	return $template;
}
add_filter( 'wc_get_template', 'apexvalue_force_quote_thankyou_template', 999, 3 );

/**
 * Warn in wp-admin if a plugin update reverts the email price-guard patches.
 *
 * Three patches live inside plugin files (not the theme), because those
 * plugins render the quote emails on the live send path:
 *
 *  1. email-templates (WooMail) templates/woo/emails/email-order-details.php
 *     — Price column header + totals rows wrapped in the `_quote_status`
 *     guard (2 occurrences).
 *  2. email-templates (WooMail) templates/woo/emails/email-order-items.php
 *     — per-item subtotal cell behind the same guard (1 occurrence).
 *  3. email-templates (WooMail) class-mailtpl-woomail-composer.php — the
 *     footer wp_kses whitelist that keeps `<br />` line breaks alive in the
 *     email footer.
 *
 * Updating or reinstalling the Email Templates plugin overwrites all three.
 * The theme's own woocommerce/emails/ overrides then take over for the
 * standard chain, but WooMail's bundled templates are what actually render
 * on this site — so a revert silently puts prices back into quote emails.
 * This notice checks the marker strings on every admin page load and tells
 * the operator exactly which patch needs re-applying.
 *
 * @return void
 */
function apexvalue_woomail_patch_watchdog() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$et_dir = WP_PLUGIN_DIR . '/email-templates';

	$checks = array(
		'order table (Price column + totals)' => array(
			'file'   => $et_dir . '/templates/woo/emails/email-order-details.php',
			'marker' => '_quote_status',
			'count'  => 2,
		),
		'per-item subtotal cell'              => array(
			'file'   => $et_dir . '/templates/woo/emails/email-order-items.php',
			'marker' => '_quote_status',
			'count'  => 1,
		),
		'footer line breaks (kses whitelist)' => array(
			'file'   => $et_dir . '/class-mailtpl-woomail-composer.php',
			'marker' => "'br' => array(),",
			'count'  => 1,
		),
	);

	$reverted = array();
	foreach ( $checks as $label => $check ) {
		if ( ! file_exists( $check['file'] ) ) {
			$reverted[] = sprintf( '%s — file missing: %s', $label, str_replace( WP_PLUGIN_DIR . '/', '', $check['file'] ) );
			continue;
		}
		$contents = file_get_contents( $check['file'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $contents || substr_count( $contents, $check['marker'] ) < $check['count'] ) {
			$reverted[] = sprintf( '%s — %s', $label, str_replace( WP_PLUGIN_DIR . '/', '', $check['file'] ) );
		}
	}

	if ( empty( $reverted ) ) {
		return;
	}

	$items = '<ul style="margin:6px 0 6px 18px;list-style:disc;">';
	foreach ( $reverted as $item ) {
		$items .= '<li><code>' . esc_html( $item ) . '</code></li>';
	}
	$items .= '</ul>';

	printf(
		'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p>%3$s<p>%4$s</p></div>',
		esc_html__( 'Quotation email price-guard needs attention.', 'apexvalue' ),
		esc_html__( 'A plugin update appears to have reverted the patches that hide prices in quotation emails:', 'apexvalue' ),
		$items, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html() above.
		esc_html__( 'Re-apply the _quote_status guards and the footer kses whitelist (see the ops log), or send a test quotation request to verify prices are still hidden.', 'apexvalue' )
	);
}
add_action( 'admin_notices', 'apexvalue_woomail_patch_watchdog' );
