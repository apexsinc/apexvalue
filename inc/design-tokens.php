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
	$hero    = get_theme_mod( 'apexvalue_hero_bg', '#1d3d5c' );

	// Adaptive footer text colors — readable on any footer background.
	if ( apexvalue_hex_luminance( $footer ) < 0.35 ) {
		$f_ink   = '#f4f7fa';
		$f_muted = 'rgba(244,247,250,0.72)';
		$f_line  = 'rgba(255,255,255,0.12)';
		$f_link  = '#a9c6e0';
	} else {
		$f_ink   = '#24303c';
		$f_muted = '#5c6b7a';
		$f_line  = 'rgba(0,0,0,0.08)';
		$f_link  = '#33688f';
	}

	// Darker accent for hover/focus on accent-filled buttons: 25% towards
	// black. Only near-black accents keep their own value — darkening those
	// further would make the hover state invisible.
	$ch = array(
		hexdec( substr( $accent, 1, 2 ) ),
		hexdec( substr( $accent, 3, 2 ) ),
		hexdec( substr( $accent, 5, 2 ) ),
	);
	foreach ( $ch as $i => $c ) {
		$ch[ $i ] = (int) max( 0, round( $c * 0.75 ) );
	}
	$accent_dark = sprintf( '#%02x%02x%02x', $ch[0], $ch[1], $ch[2] );
	if ( apexvalue_hex_luminance( $accent_dark ) < 0.04 ) {
		$accent_dark = $accent; // Already near-black — a hover shift would be invisible.
	}

	$base = '';
	foreach ( array(
		'--apx-accent'       => $accent,
		'--apx-accent-dark'  => $accent_dark,
		'--apx-ink'          => $heading,
		'--apx-body'         => $text,
		'--apx-header-bg'    => $header,
		'--apx-footer-bg'    => $footer,
		'--apx-footer-ink'   => $f_ink,
		'--apx-footer-muted' => $f_muted,
		'--apx-footer-line'  => $f_line,
		'--apx-footer-link'  => $f_link,
	) as $name => $value ) {
		$base .= $name . ':' . $value . ';';
	}

	$css = sprintf(
		':root{%1$s--apx-hero-bg:%2$s;}',
		esc_attr( $base ),
		esc_attr( $hero )
	);

	wp_add_inline_style( 'storefront-child-style', $css );
}
add_action( 'wp_enqueue_scripts', 'apexvalue_design_tokens_css', 40 );

/**
 * Relative luminance of a hex color (WCAG formula, 0 = black, 1 = white).
 *
 * @param string $hex Hex color, with or without '#'.
 * @return float
 */
function apexvalue_hex_luminance( $hex ) {
	$hex = ltrim( (string) $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) ) {
		return 1; // Unparseable — assume light background.
	}

	$channels = array(
		hexdec( substr( $hex, 0, 2 ) ) / 255,
		hexdec( substr( $hex, 2, 2 ) ) / 255,
		hexdec( substr( $hex, 4, 2 ) ) / 255,
	);

	$lum = 0;
	foreach ( $channels as $i => $c ) {
		$c = ( $c <= 0.03928 ) ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		$lum += $c * array( 0.2126, 0.7152, 0.0722 )[ $i ];
	}

	return $lum;
}

/**
 * The front page template (template-fullwidth) suppresses the page header,
 * which leaves the homepage without an H1. Add a visually-hidden one for
 * accessibility and SEO without changing the visual design.
 *
 * Skipped when the hero is active — the hero renders the visible H1.
 */
function apexvalue_front_page_h1() {
	if ( ! is_front_page() || is_page_template( 'template-homepage.php' ) ) {
		return;
	}

	if ( function_exists( 'apexvalue_hero_enabled' ) && apexvalue_hero_enabled() ) {
		return;
	}

	printf(
		'<h1 class="screen-reader-text">%s</h1>',
		esc_html( get_the_title( get_queried_object_id() ) )
	);
}
add_action( 'storefront_content_top', 'apexvalue_front_page_h1', 5 );
