<?php
/**
 * Top utility bar for Apex Value.
 *
 * Thin strip above the header with the authorized-distributor note and
 * contact shortcuts. Phone/email reuse the footer Customizer values so
 * there is a single source of truth.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer settings for the top bar.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function apexvalue_topbar_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'apexvalue_topbar',
		array(
			'title'    => __( 'Apex Value Top Bar', 'apexvalue' ),
			'priority' => 33,
		)
	);

	$wp_customize->add_setting(
		'apexvalue_topbar_promo',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'apexvalue_topbar_promo',
		array(
			'label'   => __( 'Promo text', 'apexvalue' ),
			'section' => 'apexvalue_topbar',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'apexvalue_topbar_promo_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);

	$wp_customize->add_control(
		'apexvalue_topbar_promo_url',
		array(
			'label'   => __( 'Promo link', 'apexvalue' ),
			'section' => 'apexvalue_topbar',
			'type'    => 'url',
		)
	);
}
add_action( 'customize_register', 'apexvalue_topbar_customize_register', 20 );

/**
 * Render the utility bar before the header.
 */
function apexvalue_topbar_render() {
	$phone     = get_theme_mod( 'apexvalue_footer_phone', '0917 312 9336' );
	$email     = get_theme_mod( 'apexvalue_footer_email', 'apexsinc@apexvalue.com' );
	$promo     = get_theme_mod( 'apexvalue_topbar_promo', '' );
	$promo_url = get_theme_mod( 'apexvalue_topbar_promo_url', '' );

	$phone_href = preg_replace( '/[^0-9+]/', '', (string) $phone );
	if ( '' !== $phone_href && '0' === $phone_href[0] ) {
		$phone_href = '+63' . substr( $phone_href, 1 );
	}
	?>
	<div class="apex-topbar">
		<div class="col-full apex-topbar__inner">
			<p class="apex-topbar__note"><?php esc_html_e( 'Authorized Davis Instruments distributor in the Philippines', 'apexvalue' ); ?></p>
			<?php if ( '' !== trim( (string) $promo ) ) : ?>
				<p class="apex-topbar__promo"><?php if ( '' !== trim( (string) $promo_url ) ) : ?><a href="<?php echo esc_url( $promo_url ); ?>"><?php echo esc_html( $promo ); ?></a><?php else : ?><span><?php echo esc_html( $promo ); ?></span><?php endif; ?></p>
			<?php endif; ?>
			<ul class="apex-topbar__links">
				<?php if ( '' !== trim( (string) $phone ) ) : ?>
					<li><a href="tel:<?php echo esc_attr( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a></li>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $email ) ) : ?>
					<li class="apex-topbar__email"><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
	<?php
}
add_action( 'storefront_before_header', 'apexvalue_topbar_render' );
