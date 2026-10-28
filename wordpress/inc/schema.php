<?php
/**
 * Structured data additions for Apex Value.
 *
 * The FAQs category archive lists full question + answer pairs on the
 * page, so it gets FAQPage JSON-LD built from the posts actually shown
 * (paginated archives only mark up their own page's posts). Product
 * schema is left entirely to WooCommerce — richer and already valid.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQPage JSON-LD for the FAQs category archive.
 */
function apexvalue_faq_schema() {
	if ( ! is_category( 'faqs' ) ) {
		return;
	}

	global $wp_query;
	if ( empty( $wp_query->posts ) || ! is_array( $wp_query->posts ) ) {
		return;
	}

	$entities = array();
	foreach ( $wp_query->posts as $faq ) {
		if ( 'post' !== get_post_type( $faq ) ) {
			continue;
		}

		$question = get_the_title( $faq );
		// Strip block-comment markers and tags so the answer matches
		// the visible text of the post content.
		$answer = trim( preg_replace( '/<!--.*?-->/s', '', (string) $faq->post_content ) );
		$answer = wp_strip_all_tags( $answer );

		if ( '' === $question || '' === $answer ) {
			continue;
		}

		$entities[] = array(
			'@type'           => 'Question',
			'name'            => $question,
			'acceptedAnswer'  => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}

	if ( empty( $entities ) ) {
		return;
	}

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}
add_action( 'wp_head', 'apexvalue_faq_schema', 90 );
