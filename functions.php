<?php
/**
 * Apex Value child theme bootstrap.
 *
 * IMPORTANT: WordPress loads the child theme's functions.php BEFORE the
 * parent theme's. The pluggable overrides below must therefore be defined
 * at the top level (not inside hooks) so Storefront's function_exists()
 * guards skip their own versions.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APEXVALUE_VERSION', '1.10.1' );

/**
 * Pluggable Storefront overrides (cart icon + header cart markup).
 */
require get_stylesheet_directory() . '/inc/template-overrides.php';

/**
 * Extra template tweaks (menu cleanup, misc filters).
 */
require get_stylesheet_directory() . '/inc/template-functions.php';

/**
 * Design tokens synced with the Customizer + front-page H1.
 */
require get_stylesheet_directory() . '/inc/design-tokens.php';

/**
 * Pre-footer CTA band (Customizer-editable).
 */
require get_stylesheet_directory() . '/inc/cta-band.php';

/**
 * Homepage "How quoting works" explainer (Customizer-editable).
 */
require get_stylesheet_directory() . '/inc/how-it-works.php';

/**
 * Homepage hero (Customizer-editable).
 */
require get_stylesheet_directory() . '/inc/hero.php';

/**
 * Three-column footer (pluggable storefront_footer_widgets override).
 */
require get_stylesheet_directory() . '/inc/footer.php';

/**
 * Request-a-Quotation flow: header CTA, mobile quote link,
 * quotation price badge and quote-basket note.
 */
require get_stylesheet_directory() . '/inc/quote-flow.php';

/**
 * Image performance filters (LCP + lazy-loading gaps).
 */
require get_stylesheet_directory() . '/inc/performance.php';

/**
 * Structured data additions (FAQPage on the FAQs archive).
 */
require get_stylesheet_directory() . '/inc/schema.php';

/**
 * Theme customizations.
 */
require get_stylesheet_directory() . '/inc/class-apexvalue.php';
