<?php
/**
 * Footer for Apex Value.
 *
 * Replaces the (empty) Storefront footer widget area with a clean
 * three-column footer: brand/about + social, quick links, contact.
 *
 * All copy lives in Appearance → Customize → Apex Value Footer.
 * If any footer-* widget sidebar becomes active, the original Storefront
 * widget rendering is used instead (nothing is lost).
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer settings for the footer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function apexvalue_footer_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'apexvalue_footer',
		array(
			'title'       => __( 'Apex Value Footer', 'apexvalue' ),
			'priority'    => 35,
			'description' => __( 'Leave a column heading empty to hide that column. Footer widgets (if any) always take priority.', 'apexvalue' ),
		)
	);

	$fields = array(
		'apexvalue_footer_about'   => array(
			'default'           => __( 'Apex Value is your authorized Davis Instruments distributor in the Philippines — genuine hardware, expert advice and local support for weather, marine and environmental monitoring.', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_textarea_field',
			'type'              => 'textarea',
			'label'             => __( 'About text', 'apexvalue' ),
		),
		'apexvalue_footer_fb'      => array(
			'default'           => 'https://fb.com/apexsinc',
			'sanitize_callback' => 'esc_url_raw',
			'type'              => 'url',
			'label'             => __( 'Facebook URL', 'apexvalue' ),
		),
		'apexvalue_footer_email'   => array(
			'default'           => 'apexsinc@apexvalue.com',
			'sanitize_callback' => 'sanitize_email',
			'type'              => 'email',
			'label'             => __( 'Email address', 'apexvalue' ),
		),
		'apexvalue_footer_phone'   => array(
			'default'           => '0917 312 9336',
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'text',
			'label'             => __( 'Phone / Viber', 'apexvalue' ),
		),
		'apexvalue_footer_address' => array(
			'default'           => __( 'Cebu, Philippines', 'apexvalue' ),
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'text',
			'label'             => __( 'Address', 'apexvalue' ),
		),
	);

	foreach ( $fields as $id => $field ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => $field['sanitize_callback'],
			)
		);

		$wp_customize->add_control(
			$id,
			array(
				'label'   => $field['label'],
				'section' => 'apexvalue_footer',
				'type'    => $field['type'],
			)
		);
	}
}
add_action( 'customize_register', 'apexvalue_footer_customize_register', 20 );

/**
 * Whether any Storefront footer widget area is in use.
 *
 * @return bool
 */
function apexvalue_footer_has_widgets() {
	return is_active_sidebar( 'footer-1' )
		|| is_active_sidebar( 'footer-2' )
		|| is_active_sidebar( 'footer-3' )
		|| is_active_sidebar( 'footer-4' );
}

/**
 * Render the three-column footer (pluggable override of Storefront's
 * footer_widgets, which currently outputs nothing on this site).
 */
function storefront_footer_widgets() {
	if ( apexvalue_footer_has_widgets() ) {
		storefront_default_footer_widgets();
		return;
	}

	$about   = get_theme_mod( 'apexvalue_footer_about', __( 'Apex Value is your authorized Davis Instruments distributor in the Philippines — genuine hardware, expert advice and local support for weather, marine and environmental monitoring.', 'apexvalue' ) );
	$fb      = get_theme_mod( 'apexvalue_footer_fb', 'https://fb.com/apexsinc' );
	$email   = get_theme_mod( 'apexvalue_footer_email', 'apexsinc@apexvalue.com' );
	$phone   = get_theme_mod( 'apexvalue_footer_phone', '0917 312 9336' );
	$address = get_theme_mod( 'apexvalue_footer_address', __( 'Cebu, Philippines', 'apexvalue' ) );

	// Quick links reuse the primary navigation menu (falls back to pages).
	$links = wp_nav_menu(
		array(
			'theme_location' => 'primary',
			'container'      => false,
			'items_wrap'     => '%3$s',
			'fallback_cb'    => 'wp_page_menu',
			'depth'          => 1,
			'echo'           => false,
		)
	);
	?>
	<div class="footer-widgets col-3 apex-footer fix">
		<div class="block apex-footer__brand">
			<span class="apex-footer__logo">Apex Value</span>
			<?php if ( '' !== trim( (string) $about ) ) : ?>
				<p class="apex-footer__about"><?php echo esc_html( $about ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== trim( (string) $fb ) ) : ?>
				<a class="apex-footer__social" href="<?php echo esc_url( $fb ); ?>" rel="noopener nofollow" target="_blank">
					<span class="screen-reader-text"><?php esc_html_e( 'Facebook', 'apexvalue' ); ?></span>
					<span class="apex-footer__social-icon" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
		</div>

		<div class="block apex-footer__links">
			<h2 class="apex-footer__heading"><?php esc_html_e( 'Explore', 'apexvalue' ); ?></h2>
			<?php if ( ! empty( $links ) ) : ?>
				<ul class="apex-footer__menu">
					<?php echo wp_kses_post( $links ); ?>
				</ul>
			<?php endif; ?>

			<?php
			$collections = function_exists( 'apexvalue_get_collections' ) ? apexvalue_get_collections() : array();
			if ( ! empty( $collections ) ) :
				?>
				<h2 class="apex-footer__heading"><?php esc_html_e( 'Collections', 'apexvalue' ); ?></h2>
				<ul class="apex-footer__menu">
					<?php foreach ( $collections as $term ) : ?>
						<li class="menu-item"><a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="block apex-footer__contact">
			<h2 class="apex-footer__heading"><?php esc_html_e( 'Contact', 'apexvalue' ); ?></h2>
			<ul class="apex-footer__contact-list">
				<?php if ( '' !== trim( (string) $email ) ) : ?>
					<li class="apex-footer__contact-item apex-footer__contact-item--email">
						<a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a>
					</li>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $phone ) ) : ?>
					<li class="apex-footer__contact-item apex-footer__contact-item--phone"><?php echo esc_html( $phone ); ?></li>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $address ) ) : ?>
					<li class="apex-footer__contact-item apex-footer__contact-item--address"><?php echo esc_html( $address ); ?></li>
				<?php endif; ?>
				</ul>
		</div>
	</div>
	<?php
}

/**
 * Storefront's original footer-widgets renderer (kept so widget areas
 * keep working if they are ever activated).
 */
function storefront_default_footer_widgets() {
	$rows    = intval( apply_filters( 'storefront_footer_widget_rows', 1 ) );
	$regions = intval( apply_filters( 'storefront_footer_widget_columns', 4 ) );

	for ( $row = 1; $row <= $rows; $row++ ) :
		// Defines the number of active columns in this footer row.
		for ( $region = $regions; 0 < $region; $region-- ) {
			if ( is_active_sidebar( 'footer-' . esc_attr( $region + $regions * ( $row - 1 ) ) ) ) {
				$columns = $region;
				break;
			}
		}

		if ( isset( $columns ) ) :
			?>
			<div class="<?php echo esc_attr( 'footer-widgets row-' . $row . ' col-' . $columns . ' fix' ); ?>">
			<?php
			for ( $column = 1; $column <= $columns; $column++ ) :
				$footer_n = $column + $regions * ( $row - 1 );

				if ( is_active_sidebar( 'footer-' . esc_attr( $footer_n ) ) ) :
					?>
					<div class="block footer-widget-<?php echo esc_attr( $column ); ?>">
						<?php dynamic_sidebar( 'footer-' . esc_attr( $footer_n ) ); ?>
					</div>
					<?php
				endif;
			endfor;
			?>
			</div><!-- .footer-widgets.row-<?php echo esc_attr( $row ); ?> -->
			<?php
			unset( $columns );
		endif;
	endfor;
}
