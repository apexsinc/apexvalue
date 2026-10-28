<?php
/**
 * Homepage reviews strip (reuses the Google-reviews feed).
 *
 * Renders the customer-reviews shortcode in a slim social-proof band
 * directly above the CTA band. All copy is editable in
 * Appearance → Customize → Apex Value Reviews. Emptying the heading
 * hides the section entirely.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer settings for the section copy.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function apexvalue_reviews_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'apexvalue_reviews',
		array(
			'title'       => __( 'Apex Value Reviews', 'apexvalue' ),
			'priority'    => 37,
			'description' => __( 'Shown on the homepage above the CTA band. Leave the heading empty to hide the section.', 'apexvalue' ),
		)
	);

	$fields = array(
		'apexvalue_reviews_heading'   => array(
			'default'           => __( 'What our customers say', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => __( 'Heading', 'apexvalue' ),
			'type'              => 'text',
		),
		'apexvalue_reviews_link_text' => array(
			'default'           => __( 'Read all reviews', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => __( 'Link text', 'apexvalue' ),
			'type'              => 'text',
		),
	);

	foreach ( $fields as $key => $field ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => $field['sanitize_callback'],
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'   => $field['label'],
				'section' => 'apexvalue_reviews',
				'type'    => $field['type'],
			)
		);
	}
}
add_action( 'customize_register', 'apexvalue_reviews_customize_register', 20 );

/**
 * Render the section on the front page.
 */
function apexvalue_reviews_render() {
	if ( ! is_front_page() ) {
		return;
	}

	$heading = get_theme_mod( 'apexvalue_reviews_heading', __( 'What our customers say', 'apexvalue' ) );
	if ( '' === trim( (string) $heading ) ) {
		return;
	}

	$link_text = get_theme_mod( 'apexvalue_reviews_link_text', __( 'Read all reviews', 'apexvalue' ) );

	$reviews_page = get_page_by_path( 'reviews' );
	$reviews_url  = $reviews_page ? get_permalink( $reviews_page ) : home_url( '/reviews/' );
	?>
	<section class="apex-reviews apex-reveal" aria-labelledby="apex-reviews-heading">
		<div class="col-full apex-reviews__inner">
			<h2 class="apex-reviews__heading" id="apex-reviews-heading"><?php echo esc_html( $heading ); ?></h2>
			<?php if ( shortcode_exists( 'grw' ) ) : ?>
				<div class="apex-reviews__feed"><?php echo do_shortcode( '[grw id=2384]' ); ?></div>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $link_text ) ) : ?>
				<a class="apex-reviews__link" href="<?php echo esc_url( $reviews_url ); ?>"><?php echo esc_html( $link_text ); ?> &rarr;</a>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
add_action( 'storefront_before_footer', 'apexvalue_reviews_render', 15 );
