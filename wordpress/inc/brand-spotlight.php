<?php
/**
 * Brand spotlight homepage section.
 *
 * Data-driven: renders one card per `product_brand` term (thumbnail from
 * term meta when present, product count always). Nothing is hardcoded —
 * the section adapts as brands are added or removed in the dashboard.
 *
 * All copy is editable in Appearance → Customize → Apex Value Brand Spotlight.
 * Emptying the heading hides the section entirely.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer settings for the brand spotlight copy.
 */
function apexvalue_brand_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'apexvalue_brand',
		array(
			'title'       => __( 'Apex Value Brand Spotlight', 'apexvalue' ),
			'priority'    => 37,
			'description' => __( 'Shown on the homepage after the catalogue strip. Cards are generated from your product brands. Leave the heading empty to hide the section.', 'apexvalue' ),
		)
	);

	$fields = array(
		'apexvalue_brand_heading' => array(
			'default'           => __( 'The brands we carry', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => __( 'Heading', 'apexvalue' ),
			'type'              => 'text',
		),
		'apexvalue_brand_lede'    => array(
			'default'           => __( 'Authorised source for every brand we stock — genuine products, local warranty support, and quotations handled by our own team.', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_textarea_field',
			'label'             => __( 'Intro text', 'apexvalue' ),
			'type'              => 'textarea',
		),
		'apexvalue_brand_btn_txt' => array(
			'default'           => __( 'Browse all products', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => __( 'Button text (empty hides button)', 'apexvalue' ),
			'type'              => 'text',
		),
		'apexvalue_brand_btn_url' => array(
			'default'           => home_url( '/products/' ),
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
				'section' => 'apexvalue_brand',
				'type'    => $field['type'],
			)
		);
	}
}
add_action( 'customize_register', 'apexvalue_brand_customize_register', 20 );

/**
 * Render the brand spotlight on the front page.
 */
function apexvalue_brand_spotlight() {
	if ( ! is_front_page() ) {
		return;
	}

	$heading = get_theme_mod( 'apexvalue_brand_heading', __( 'The brands we carry', 'apexvalue' ) );
	if ( '' === trim( (string) $heading ) ) {
		return;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_brand',
			'hide_empty' => true,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return;
	}

	$lede    = get_theme_mod( 'apexvalue_brand_lede', __( 'Authorised source for every brand we stock — genuine products, local warranty support, and quotations handled by our own team.', 'apexvalue' ) );
	$btn_txt = get_theme_mod( 'apexvalue_brand_btn_txt', __( 'Browse all products', 'apexvalue' ) );
	$btn_url = get_theme_mod( 'apexvalue_brand_btn_url', home_url( '/products/' ) );
	?>
	<section class="apex-brandspot apex-reveal" aria-labelledby="apex-brandspot-heading">
		<div class="col-full apex-brandspot__inner">
			<h2 class="apex-brandspot__heading" id="apex-brandspot-heading"><?php echo esc_html( $heading ); ?></h2>
			<?php if ( '' !== trim( (string) $lede ) ) : ?>
				<p class="apex-brandspot__lede"><?php echo esc_html( $lede ); ?></p>
			<?php endif; ?>
			<ul class="apex-brandspot__grid">
				<?php foreach ( $terms as $term ) : ?>
					<?php
					$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
					$count    = (int) $term->count;
					/* translators: %d: number of products from this brand. */
					$count_txt = sprintf( _n( '%d product', '%d products', $count, 'apexvalue' ), $count );
					?>
					<li class="apex-brandspot__card">
						<a class="apex-brandspot__link" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
							<span class="apex-brandspot__thumb" aria-hidden="true">
								<?php if ( $thumb_id ) : ?>
									<?php echo wp_get_attachment_image( $thumb_id, 'thumbnail', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
								<?php else : ?>
									<span class="apex-brandspot__monogram"><?php echo esc_html( mb_substr( $term->name, 0, 1 ) ); ?></span>
								<?php endif; ?>
							</span>
							<span class="apex-brandspot__name"><?php echo esc_html( $term->name ); ?></span>
							<span class="apex-brandspot__count"><?php echo esc_html( $count_txt ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( '' !== trim( (string) $btn_txt ) && '' !== trim( (string) $btn_url ) ) : ?>
				<div class="apex-brandspot__actions">
					<a class="apex-brandspot__btn" href="<?php echo esc_url( $btn_url ); ?>"><?php echo esc_html( $btn_txt ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
add_action( 'storefront_before_footer', 'apexvalue_brand_spotlight', 5 );
