<?php
/**
 * Pre-footer call-to-action band for Apex Value.
 *
 * Renders above the site footer on content pages. All text is editable
 * in Appearance → Customize → Apex Value CTA Band (no hardcoded copy).
 * Leave the headline empty to hide the band entirely.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer settings for the CTA band.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function apexvalue_cta_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'apexvalue_cta',
		array(
			'title'       => __( 'Apex Value CTA Band', 'apexvalue' ),
			'priority'    => 35,
			'description' => __( 'Shown above the footer on content pages. Leave the headline empty to hide the band.', 'apexvalue' ),
		)
	);

	$wp_customize->add_setting(
		'apexvalue_cta_heading',
		array(
			'default'           => __( 'Need help choosing the right weather station?', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_cta_heading',
		array(
			'label'   => __( 'Headline', 'apexvalue' ),
			'section' => 'apexvalue_cta',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_cta_text',
		array(
			'default'           => __( 'Talk to the Philippines’ authorized Davis Instruments distributor — we’ll point you to the right setup for your home, farm or vessel.', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_cta_text',
		array(
			'label'   => __( 'Supporting text', 'apexvalue' ),
			'section' => 'apexvalue_cta',
			'type'    => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_cta_button_text',
		array(
			'default'           => __( 'Request a Quote', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_cta_button_text',
		array(
			'label'   => __( 'Button label', 'apexvalue' ),
			'section' => 'apexvalue_cta',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_cta_button_url',
		array(
			'default'           => home_url( '/inquiry/' ),
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_cta_button_url',
		array(
			'label'       => __( 'Button link', 'apexvalue' ),
			'description' => __( 'Paste the full URL, e.g. https://www.apexvalue.com/contact-us/', 'apexvalue' ),
			'section'     => 'apexvalue_cta',
			'type'        => 'url',
		)
	);
}
add_action( 'customize_register', 'apexvalue_cta_customize_register', 20 );

/**
 * Render the CTA band above the footer.
 */
function apexvalue_cta_band() {
	// Skip transactional pages — nobody needs a sales CTA mid-checkout.
	if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() || is_account_page() ) ) {
		return;
	}

	$heading     = get_theme_mod( 'apexvalue_cta_heading', __( 'Need help choosing the right weather station?', 'apexvalue' ) );
	$text        = get_theme_mod( 'apexvalue_cta_text', __( 'Talk to the Philippines’ authorized Davis Instruments distributor — we’ll point you to the right setup for your home, farm or vessel.', 'apexvalue' ) );
	$button_text = get_theme_mod( 'apexvalue_cta_button_text', __( 'Request a Quote', 'apexvalue' ) );
	$button_url  = get_theme_mod( 'apexvalue_cta_button_url', home_url( '/inquiry/' ) );

	if ( '' === trim( (string) $heading ) ) {
		return; // Hidden by choice.
	}
	?>
	<section class="apexvalue-cta apex-reveal" aria-labelledby="apexvalue-cta-heading">
		<div class="col-full apexvalue-cta__inner">
			<div class="apexvalue-cta__copy">
				<h2 class="apexvalue-cta__heading" id="apexvalue-cta-heading"><?php echo esc_html( $heading ); ?></h2>
				<?php if ( '' !== trim( (string) $text ) ) : ?>
					<p class="apexvalue-cta__text"><?php echo esc_html( $text ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( '' !== trim( (string) $button_text ) && '' !== trim( (string) $button_url ) ) : ?>
				<a class="apexvalue-cta__button" href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html( $button_text ); ?></a>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
add_action( 'storefront_before_footer', 'apexvalue_cta_band', 20 );

/**
 * Live-preview JS for the Customizer (postMessage transport).
 */
function apexvalue_cta_customize_preview_js() {
	wp_enqueue_script(
		'apexvalue-cta-preview',
		get_stylesheet_directory_uri() . '/assets/js/cta-preview.js',
		array( 'customize-preview' ),
		APEXVALUE_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'apexvalue_cta_customize_preview_js' );
