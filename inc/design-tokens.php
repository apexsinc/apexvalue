<?php
/**
 * Design tokens + small head markup for Apex Value.
 *
 * Keeps the stylesheet's CSS variables in sync with the WordPress
 * Customizer, so future color changes in Appearance → Customize are
 * picked up by the child theme automatically.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print CSS custom properties derived from the Customizer.
 */
function apexvalue_design_tokens_css() {
	$accent  = get_theme_mod( 'storefront_accent_color', '#7f54b3' );
	$heading = get_theme_mod( 'storefront_heading_color', '#333333' );
	$text    = get_theme_mod( 'storefront_text_color', '#6d6d6d' );
	$header  = get_theme_mod( 'storefront_header_background_color', '#ffffff' );
	$footer  = get_theme_mod( 'storefront_footer_background_color', '#f0f0f0' );

	$css = sprintf(
		':root{--apx-accent:%1$s;--apx-ink:%2$s;--apx-body:%3$s;--apx-header-bg:%4$s;--apx-footer-bg:%5$s;}',
		esc_attr( $accent ),
		esc_attr( $heading ),
		esc_attr( $text ),
		esc_attr( $header ),
		esc_attr( $footer )
	);

	wp_add_inline_style( 'storefront-child-style', $css );
}
add_action( 'wp_enqueue_scripts', 'apexvalue_design_tokens_css', 40 );

/**
 * The front page template (template-fullwidth) suppresses the page header,
 * which leaves the homepage without an H1. Add a visually-hidden one for
 * accessibility and SEO without changing the visual design.
 */
function apexvalue_front_page_h1() {
	if ( ! is_front_page() || is_page_template( 'template-homepage.php' ) ) {
		return;
	}

	printf(
		'<h1 class="screen-reader-text">%s</h1>',
		esc_html( get_the_title( get_queried_object_id() ) )
	);
}
add_action( 'storefront_content_top', 'apexvalue_front_page_h1', 5 );
