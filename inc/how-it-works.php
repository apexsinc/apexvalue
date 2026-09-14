<?php
/**
 * "How quoting works" homepage section.
 *
 * The store's whole purpose is quotation requests, but nothing on the
 * homepage explained the flow. This adds a compact three-step explainer
 * (browse → submit → quotation) directly above the CTA band.
 *
 * All copy is editable in Appearance → Customize → Apex Value How It Works.
 * Emptying the heading hides the section entirely.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer settings for the section copy.
 */
function apexvalue_how_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'apexvalue_how',
		array(
			'title'       => __( 'Apex Value How It Works', 'apexvalue' ),
			'priority'    => 36,
			'description' => __( 'Shown on the homepage above the CTA band. Leave the heading empty to hide the section.', 'apexvalue' ),
		)
	);

	$fields = array(
		'apexvalue_how_heading'    => array(
			'default'           => __( 'How quoting works', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => __( 'Heading', 'apexvalue' ),
			'type'              => 'text',
		),
		'apexvalue_how_lede'       => array(
			'default'           => __( 'Everything ships from our Philippine warehouse — request a quotation and our team replies with availability, pricing and lead times.', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_textarea_field',
			'label'             => __( 'Intro text', 'apexvalue' ),
			'type'              => 'textarea',
		),
		'apexvalue_how_step1_t'    => array(
			'default'           => __( 'Browse the catalogue', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => __( 'Step 1 title', 'apexvalue' ),
			'type'              => 'text',
		),
		'apexvalue_how_step1_x'    => array(
			'default'           => __( 'Pick the weather or marine monitoring equipment you need and add it to your quotation basket. No account or payment required.', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_textarea_field',
			'label'             => __( 'Step 1 text', 'apexvalue' ),
			'type'              => 'textarea',
		),
		'apexvalue_how_step2_t'    => array(
			'default'           => __( 'Submit your basket', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => __( 'Step 2 title', 'apexvalue' ),
			'type'              => 'text',
		),
		'apexvalue_how_step2_x'    => array(
			'default'           => __( 'Send the basket through checkout or the inquiry form — tell us quantities, delivery location and any special requirements.', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_textarea_field',
			'label'             => __( 'Step 2 text', 'apexvalue' ),
			'type'              => 'textarea',
		),
		'apexvalue_how_step3_t'    => array(
			'default'           => __( 'Receive your quotation', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => __( 'Step 3 title', 'apexvalue' ),
			'type'              => 'text',
		),
		'apexvalue_how_step3_x'    => array(
			'default'           => __( 'Our team confirms stock, pricing and lead times, then emails you a formal quotation.', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_textarea_field',
			'label'             => __( 'Step 3 text', 'apexvalue' ),
			'type'              => 'textarea',
		),
		'apexvalue_how_btn_text'   => array(
			'default'           => __( 'Request a quotation', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => __( 'Button text', 'apexvalue' ),
			'type'              => 'text',
		),
		'apexvalue_how_btn_url'    => array(
			'default'           => home_url( '/inquiry/' ),
			'sanitize_callback' => 'esc_url_raw',
			'label'             => __( 'Button URL', 'apexvalue' ),
			'type'              => 'url',
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
				'section' => 'apexvalue_how',
				'type'    => $field['type'],
			)
		);
	}
}
add_action( 'customize_register', 'apexvalue_how_customize_register', 20 );

/**
 * Render the section on the front page.
 */
function apexvalue_how_it_works() {
	if ( ! is_front_page() ) {
		return;
	}

	$heading = get_theme_mod( 'apexvalue_how_heading', __( 'How quoting works', 'apexvalue' ) );
	if ( '' === trim( (string) $heading ) ) {
		return;
	}

	$lede    = get_theme_mod( 'apexvalue_how_lede', __( 'Everything ships from our Philippine warehouse — request a quotation and our team replies with availability, pricing and lead times.', 'apexvalue' ) );
	$btn_txt = get_theme_mod( 'apexvalue_how_btn_text', __( 'Request a quotation', 'apexvalue' ) );
	$btn_url = get_theme_mod( 'apexvalue_how_btn_url', home_url( '/inquiry/' ) );

	$steps = array(
		array(
			't' => get_theme_mod( 'apexvalue_how_step1_t', __( 'Browse the catalogue', 'apexvalue' ) ),
			'x' => get_theme_mod( 'apexvalue_how_step1_x', __( 'Pick the weather or marine monitoring equipment you need and add it to your quotation basket. No account or payment required.', 'apexvalue' ) ),
		),
		array(
			't' => get_theme_mod( 'apexvalue_how_step2_t', __( 'Submit your basket', 'apexvalue' ) ),
			'x' => get_theme_mod( 'apexvalue_how_step2_x', __( 'Send the basket through checkout or the inquiry form — tell us quantities, delivery location and any special requirements.', 'apexvalue' ) ),
		),
		array(
			't' => get_theme_mod( 'apexvalue_how_step3_t', __( 'Receive your quotation', 'apexvalue' ) ),
			'x' => get_theme_mod( 'apexvalue_how_step3_x', __( 'Our team confirms stock, pricing and lead times, then emails you a formal quotation.', 'apexvalue' ) ),
		),
	);
	?>
	<section class="apex-how apex-reveal" aria-labelledby="apex-how-heading">
		<div class="col-full apex-how__inner">
			<h2 class="apex-how__heading" id="apex-how-heading"><?php echo esc_html( $heading ); ?></h2>
			<?php if ( '' !== trim( (string) $lede ) ) : ?>
				<p class="apex-how__lede"><?php echo esc_html( $lede ); ?></p>
			<?php endif; ?>
			<ol class="apex-how__steps">
				<?php foreach ( $steps as $i => $step ) : ?>
					<li class="apex-how__step">
						<span class="apex-how__num" aria-hidden="true"><?php echo (int) ( $i + 1 ); ?></span>
						<h3 class="apex-how__title"><?php echo esc_html( $step['t'] ); ?></h3>
						<div class="apex-how__text"><?php echo esc_html( $step['x'] ); ?></div>
					</li>
				<?php endforeach; ?>
			</ol>
			<?php if ( '' !== trim( (string) $btn_txt ) && '' !== trim( (string) $btn_url ) ) : ?>
				<div class="apex-how__actions">
					<a class="apex-how__btn" href="<?php echo esc_url( $btn_url ); ?>"><?php echo esc_html( $btn_txt ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
add_action( 'storefront_before_footer', 'apexvalue_how_it_works', 10 );
