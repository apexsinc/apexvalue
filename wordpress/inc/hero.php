<?php
/**
 * Homepage hero for Apex Value.
 *
 * Renders a full-bleed hero above the homepage content. All copy is
 * editable in Appearance → Customize → Apex Value Hero. Empty headline
 * disables the hero (front page keeps its fallback hidden H1).
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer settings for the hero.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function apexvalue_hero_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'apexvalue_hero',
		array(
			'title'       => __( 'Apex Value Hero', 'apexvalue' ),
			'priority'    => 34,
			'description' => __( 'Shown at the top of the homepage. Leave the headline empty to hide the hero.', 'apexvalue' ),
		)
	);

	$wp_customize->add_setting(
		'apexvalue_hero_kicker',
		array(
			'default'           => __( 'Authorized Davis Instruments Distributor', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_hero_kicker',
		array(
			'label'   => __( 'Kicker (small text above headline)', 'apexvalue' ),
			'section' => 'apexvalue_hero',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_hero_headline',
		array(
			'default'           => __( 'Professional weather & marine monitoring for the Philippines', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_hero_headline',
		array(
			'label'   => __( 'Headline', 'apexvalue' ),
			'section' => 'apexvalue_hero',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_hero_text',
		array(
			'default'           => __( 'From Vantage Vue stations to complete EnviroMonitor networks — genuine hardware, expert advice and local support for homes, farms, coastlines and vessels.', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_hero_text',
		array(
			'label'   => __( 'Supporting text', 'apexvalue' ),
			'section' => 'apexvalue_hero',
			'type'    => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_hero_cta_text',
		array(
			'default'           => __( 'Request a Quote', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_hero_cta_text',
		array(
			'label'   => __( 'Primary button label', 'apexvalue' ),
			'section' => 'apexvalue_hero',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_hero_cta_url',
		array(
			'default'           => home_url( '/inquiry/' ),
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_hero_cta_url',
		array(
			'label'   => __( 'Primary button link', 'apexvalue' ),
			'section' => 'apexvalue_hero',
			'type'    => 'url',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_hero_cta2_text',
		array(
			'default'           => __( 'Browse products', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_hero_cta2_text',
		array(
			'label'   => __( 'Secondary button label', 'apexvalue' ),
			'section' => 'apexvalue_hero',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_hero_cta2_url',
		array(
			'default'           => home_url( '/products/' ),
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'apexvalue_hero_cta2_url',
		array(
			'label'   => __( 'Secondary button link', 'apexvalue' ),
			'section' => 'apexvalue_hero',
			'type'    => 'url',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_hero_bg',
		array(
			'default'           => '#1d3d5c',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'apexvalue_hero_bg',
			array(
				'label'       => __( 'Background color', 'apexvalue' ),
				'description' => __( 'Use a deep navy/steel tone for best contrast with white text.', 'apexvalue' ),
				'section'     => 'apexvalue_hero',
			)
		)
	);

	$wp_customize->add_setting(
		'apexvalue_hero_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'apexvalue_hero_image',
			array(
				'label'       => __( 'Side image (optional)', 'apexvalue' ),
				'description' => __( 'Product or station photo shown beside the headline. Leave empty to use the built-in station photo.', 'apexvalue' ),
				'section'     => 'apexvalue_hero',
				'mime_type'   => 'image',
			)
		)
	);
}
add_action( 'customize_register', 'apexvalue_hero_customize_register', 20 );

/** Heading text for the hero (shared by the enabled-check and renderer). */
function apexvalue_hero_headline() {
	return get_theme_mod( 'apexvalue_hero_headline', __( 'Professional weather & marine monitoring for the Philippines', 'apexvalue' ) );
}

/**
 * Whether the hero is currently shown (front page + non-empty headline).
 *
 * @return bool
 */
function apexvalue_hero_enabled() {
	if ( ! is_front_page() ) {
		return false;
	}

	return '' !== trim( (string) apexvalue_hero_headline() );
}

/**
 * Hero side-image URL.
 *
 * Customizer media selection wins; otherwise fall back to the bundled
 * station photo when present in the uploads directory.
 *
 * @return string Empty when no image is available.
 */
function apexvalue_hero_image_url() {
	$attachment_id = absint( get_theme_mod( 'apexvalue_hero_image', 0 ) );
	if ( $attachment_id > 0 ) {
		$url = wp_get_attachment_image_url( $attachment_id, 'large' );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	$uploads = wp_get_upload_dir();
	$rel     = '2026/03/w_6262_Station_TT_MG_6966_Clipped_2400x1560-1024x666.webp';
	if ( isset( $uploads['basedir'], $uploads['baseurl'] ) && file_exists( trailingslashit( $uploads['basedir'] ) . $rel ) ) {
		return trailingslashit( $uploads['baseurl'] ) . $rel;
	}

	return '';
}

/**
 * Render the hero on the front page.
 */
function apexvalue_hero_render() {
	if ( ! apexvalue_hero_enabled() ) {
		return; // Hero disabled; front page falls back to the hidden H1.
	}

	$headline = apexvalue_hero_headline();
	$kicker = get_theme_mod( 'apexvalue_hero_kicker', __( 'Authorized Davis Instruments Distributor', 'apexvalue' ) );
	$text   = get_theme_mod( 'apexvalue_hero_text', __( 'From Vantage Vue stations to complete EnviroMonitor networks — genuine hardware, expert advice and local support for homes, farms, coastlines and vessels.', 'apexvalue' ) );
	$cta1_t = get_theme_mod( 'apexvalue_hero_cta_text', __( 'Request a Quote', 'apexvalue' ) );
	$cta1_u = get_theme_mod( 'apexvalue_hero_cta_url', home_url( '/inquiry/' ) );
	$cta2_t = get_theme_mod( 'apexvalue_hero_cta2_text', __( 'Browse products', 'apexvalue' ) );
	$cta2_u = get_theme_mod( 'apexvalue_hero_cta2_url', home_url( '/products/' ) );
	$media  = apexvalue_hero_image_url();
	?>
	<section class="apex-hero<?php echo '' !== $media ? ' apex-hero--media' : ''; ?>" aria-label="<?php esc_attr_e( 'Introduction', 'apexvalue' ); ?>">
		<div class="col-full apex-hero__inner">
			<div class="apex-hero__copy">
			<?php if ( '' !== trim( (string) $kicker ) ) : ?>
				<p class="apex-hero__kicker"><?php echo esc_html( $kicker ); ?></p>
			<?php endif; ?>

			<h1 class="apex-hero__title"><?php echo esc_html( $headline ); ?></h1>

			<?php if ( '' !== trim( (string) $text ) ) : ?>
				<p class="apex-hero__text"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>

			<div class="apex-hero__actions">
				<?php if ( '' !== trim( (string) $cta1_t ) && '' !== trim( (string) $cta1_u ) ) : ?>
					<a class="apex-hero__btn apex-hero__btn--primary" href="<?php echo esc_url( $cta1_u ); ?>"><?php echo esc_html( $cta1_t ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $cta2_t ) && '' !== trim( (string) $cta2_u ) ) : ?>
					<a class="apex-hero__btn apex-hero__btn--ghost" href="<?php echo esc_url( $cta2_u ); ?>"><?php echo esc_html( $cta2_t ); ?></a>
				<?php endif; ?>
			</div>

			<ul class="apex-hero__trust" aria-label="<?php esc_attr_e( 'Why shop with us', 'apexvalue' ); ?>">
				<li><?php esc_html_e( 'Genuine Davis Instruments hardware', 'apexvalue' ); ?></li>
				<li><?php esc_html_e( 'Ships from our Philippine warehouse', 'apexvalue' ); ?></li>
				<li><?php esc_html_e( 'Local expert support', 'apexvalue' ); ?></li>
			</ul>
			</div>
			<?php if ( '' !== $media ) : ?>
			<figure class="apex-hero__media">
				<img class="apex-hero__photo" src="<?php echo esc_url( $media ); ?>" alt="<?php esc_attr_e( 'Davis Instruments Vantage Vue wireless weather station', 'apexvalue' ); ?>" loading="eager" fetchpriority="high" width="1024" height="666" />
				<figcaption class="apex-hero__chips" aria-hidden="true">
					<span class="apex-hero__chip"><?php esc_html_e( 'Solar-powered wireless sensors', 'apexvalue' ); ?></span>
					<span class="apex-hero__chip"><?php esc_html_e( 'Genuine Davis hardware', 'apexvalue' ); ?></span>
				</figcaption>
			</figure>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
add_action( 'storefront_before_content', 'apexvalue_hero_render', 20 );

/**
 * Body class hook so CSS can hide the lone "Home" breadcrumb that would
 * otherwise sit above the hero.
 *
 * @param array $classes Body classes.
 * @return array
 */
function apexvalue_hero_body_class( $classes ) {
	if ( apexvalue_hero_enabled() ) {
		$classes[] = 'apex-has-hero';
	}

	return $classes;
}
add_filter( 'body_class', 'apexvalue_hero_body_class' );

/**
 * With the hero at the top, a lone "Home" breadcrumb above it is noise —
 * remove the breadcrumb from the front page while the hero is active.
 */
function apexvalue_hero_remove_front_breadcrumb() {
	if ( apexvalue_hero_enabled() ) {
		remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
	}
}
add_action( 'wp', 'apexvalue_hero_remove_front_breadcrumb' );

/**
 * Live-preview JS for the hero.
 */
function apexvalue_hero_customize_preview_js() {
	wp_enqueue_script(
		'apexvalue-hero-preview',
		get_stylesheet_directory_uri() . '/assets/js/hero-preview.js',
		array( 'customize-preview' ),
		APEXVALUE_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'apexvalue_hero_customize_preview_js' );
