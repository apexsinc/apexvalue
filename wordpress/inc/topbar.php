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
 * Render the utility bar before the header.
 */
function apexvalue_topbar_render() {
	$phone = get_theme_mod( 'apexvalue_footer_phone', '0917 312 9336' );
	$email = get_theme_mod( 'apexvalue_footer_email', 'apexsinc@apexvalue.com' );

	$phone_href = preg_replace( '/[^0-9+]/', '', (string) $phone );
	if ( '' !== $phone_href && '0' === $phone_href[0] ) {
		$phone_href = '+63' . substr( $phone_href, 1 );
	}
	?>
	<div class="apex-topbar">
		<div class="col-full apex-topbar__inner">
			<p class="apex-topbar__note"><?php esc_html_e( 'Authorized Davis Instruments distributor in the Philippines', 'apexvalue' ); ?></p>
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
