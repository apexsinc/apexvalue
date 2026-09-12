<?php
/**
 * Custom 404 template for Apex Value.
 *
 * A calm, on-brand "wrong turn" page: clear heading, quick actions
 * (search + back home), a short explore list and WooCommerce's promoted
 * products preserved from the parent template.
 *
 * @package apexvalue
 */

get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">

		<div class="error-404 not-found">
			<div class="page-content apex-404">

				<p class="apex-404__code" aria-hidden="true">404</p>

				<header class="page-header">
					<h1 class="page-title apex-404__title"><?php esc_html_e( 'That page has drifted off the map', 'apexvalue' ); ?></h1>
				</header>

				<p class="apex-404__lead"><?php esc_html_e( 'The page you\'re looking for doesn\'t exist, moved, or sold out. Let\'s get you back on track.', 'apexvalue' ); ?></p>

				<div class="apex-404__actions">
					<a class="apex-404__btn apex-404__btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to homepage', 'apexvalue' ); ?></a>
					<a class="apex-404__btn apex-404__btn--ghost" href="<?php echo esc_url( home_url( '/products/' ) ); ?>"><?php esc_html_e( 'Browse products', 'apexvalue' ); ?></a>
				</div>

				<section class="apex-404__search" aria-label="<?php esc_attr_e( 'Search', 'apexvalue' ); ?>">
					<?php
					if ( storefront_is_woocommerce_activated() ) {
						the_widget( 'WC_Widget_Product_Search' );
					} else {
						get_search_form();
					}
					?>
				</section>

				<nav class="apex-404__explore" aria-label="<?php esc_attr_e( 'Explore', 'apexvalue' ); ?>">
					<h2><?php esc_html_e( 'Popular destinations', 'apexvalue' ); ?></h2>
					<ul>
						<?php
						$links = array(
							__( 'Shop all products', 'apexvalue' ) => home_url( '/products/' ),
							__( 'Solutions', 'apexvalue' )         => home_url( '/solutions/' ),
							__( 'FAQs', 'apexvalue' )              => home_url( '/faqs/' ),
							__( 'Contact us', 'apexvalue' )        => home_url( '/contact-us/' ),
						);
						foreach ( $links as $label => $url ) :
							?>
							<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>

				<?php if ( storefront_is_woocommerce_activated() ) : ?>

					<section class="apex-404__promoted" aria-label="<?php esc_attr_e( 'New in store', 'apexvalue' ); ?>">
						<h2><?php esc_html_e( 'New in store', 'apexvalue' ); ?></h2>

						<?php
						echo storefront_do_shortcode(
							'recent_products',
							array(
								'per_page' => 4,
								'columns'  => 4,
							)
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output is escaped internally.
						?>
					</section>

				<?php endif; ?>

			</div><!-- .page-content -->
		</div><!-- .error-404 -->

	</main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
