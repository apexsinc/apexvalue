<?php
/**
 * Pluggable Storefront function overrides.
 *
 * This file is required from the child theme's functions.php at load time.
 * WordPress loads a child theme's functions.php BEFORE the parent's, so the
 * function_exists() guards in Storefront skip their own definitions and our
 * versions below win. This is the safest override mechanism — no hooks are
 * removed and WooCommerce behaviour is unchanged.
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'storefront_cart_link' ) ) {
	/**
	 * Cart icon link for the header.
	 *
	 * Replaces Storefront's "subtotal + N items" text link with a compact
	 * icon + count chip (styled in style.css). Fragment-safe: see
	 * ApexValue_Theme::cart_fragments().
	 */
	function storefront_cart_link() {
		if ( function_exists( 'apexvalue_cart_link' ) ) {
			apexvalue_cart_link();
		}
	}
}

if ( ! function_exists( 'apexvalue_cart_link' ) ) {
	/**
	 * The actual cart icon markup, reusable for fragments.
	 */
	function apexvalue_cart_link() {
		if ( function_exists( 'storefront_woo_cart_available' ) && ! storefront_woo_cart_available() ) {
			return;
		}
		?>
		<a class="cart-contents" href="<?php echo esc_url( wc_get_cart_url() ); ?>" title="<?php esc_attr_e( 'View your shopping cart', 'apexvalue' ); ?>">
			<span class="screen-reader-text">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of items in cart */
						_n( '%d item in cart', '%d items in cart', WC()->cart->get_cart_contents_count(), 'apexvalue' ),
						WC()->cart->get_cart_contents_count()
					)
				);
				?>
			</span>
			<span class="count" aria-hidden="true"><?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?></span>
		</a>
		<?php
	}
}

/**
 * Give SiteSEO's full-site search loop the product-card treatment.
 *
 * The product-specific search (`?s=...&post_type=product`) uses the standard
 * WooCommerce `ul.products` grid, which our design system already covers.
 * SiteSEO's general search renders each result as a plain WordPress
 * `<article class="... type-product">`, so product hits would show as flat,
 * unstyled entries next to styled blog hits.
 *
 * We append the same card classes our loop rules use (kept in sync with
 * section 5 of style.css). If WooCommerce is inactive the filter is a no-op.
 *
 * @param array $classes Post classes.
 * @return array
 */
function apexvalue_product_search_card_classes( $classes ) {
	// NOTE: is_woocommerce() is deliberately NOT used here — it is false on
	// search results pages. The `type-product` class is the reliable signal.
	if ( ! is_search() || ! class_exists( 'WooCommerce' ) ) {
		return $classes;
	}

	if ( in_array( 'type-product', (array) $classes, true ) ) {
		$classes[] = 'apex-card';
		$classes[] = 'apex-card--product';
	}

	return $classes;
}
add_filter( 'post_class', 'apexvalue_product_search_card_classes', 20 );

if ( ! function_exists( 'storefront_header_cart' ) ) {
	/**
	 * Header cart: icon trigger + accessible dropdown mini-cart.
	 *
	 * Markup mirrors Storefront (ul.site-header-cart with two li) so
	 * Storefront/WooCommerce CSS and JS keep working.
	 */
	function storefront_header_cart() {
		if ( ! function_exists( 'storefront_is_woocommerce_activated' ) || ! storefront_is_woocommerce_activated() ) {
			return;
		}

		$class = is_cart() ? ' current-menu-item' : '';
		?>
		<ul id="site-header-cart" class="site-header-cart menu<?php echo esc_attr( $class ); ?>">
			<li class="apexvalue-cart-trigger">
				<?php storefront_cart_link(); ?>
			</li>
			<li class="apexvalue-cart-dropdown">
				<?php the_widget( 'WC_Widget_Cart', 'title=' ); ?>
			</li>
		</ul>
		<?php
	}
}

/**
 * Legacy URL redirect: /contact/ → /contact-us/ (the live contact page).
 * 301 so search engines and stale bookmarks resolve to the real page.
 *
 * @return void
 */
function apexvalue_legacy_contact_redirect() {
	if ( is_404() && '/contact/' === trailingslashit( strtok( $_SERVER['REQUEST_URI'] ?? '', '?' ) ) ) {
		wp_safe_redirect( home_url( '/contact-us/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'apexvalue_legacy_contact_redirect' );

/**
 * Collection chips: product-category quick-links shown on shop and
 * product-category archives.
 *
 * These archives previously had no navigation into /collections/<slug>/ —
 * categories like Marine (69 products) were only reachable via search.
 * Chips render after the archive title (catalog ordering stays untouched)
 * and skip empty terms and terms that only exist to group others.
 *
 * @return void
 */
function apexvalue_collection_chips() {
	if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
		return;
	}

	// Shop post-type archive and product taxonomy archives only.
	if ( ! ( is_shop() || is_product_taxonomy() ) ) {
		return;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return;
	}

	// Drop terms whose only job is grouping children (e.g. a parent with no
	// direct products but product-bearing descendants).
	$leaf_terms = array();
	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			continue;
		}
		$leaf_terms[] = $term;
	}

	if ( empty( $leaf_terms ) ) {
		$leaf_terms = $terms;
	}

	// Leave Uncategorized out of the visual row.
	$leaf_terms = array_values(
		array_filter(
			$leaf_terms,
			static function ( $term ) {
				return 'uncategorized' !== $term->slug;
			}
		)
	);

	if ( empty( $leaf_terms ) ) {
		return;
	}

	$current_id = 0;
	if ( is_product_category() ) {
		$current_id = get_queried_object_id();
	}

	echo '<nav class="apex-collection-nav" aria-label="' . esc_attr__( 'Browse collections', 'apexvalue' ) . '">';
	echo '<ul>';
	foreach ( $leaf_terms as $term ) {
		$url   = get_term_link( $term );
		$class = 'apex-collection-nav__chip';
		if ( (int) $term->term_id === $current_id ) {
			$class .= ' is-active';
		}

		echo '<li><a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a></li>';
	}
	echo '</ul>';
	echo '</nav>';
}
add_action( 'woocommerce_before_shop_loop', 'apexvalue_collection_chips', 15 );


/**
 * Shared helper: browsable product collections.
 *
 * Leaves only product-bearing, non-grouping terms (same policy as the
 * collection chips), ordered by name. Returns WP_Term[] or an empty array.
 *
 * @return array
 */
function apexvalue_get_collections() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	// Drop terms whose only job is grouping children (no direct products).
	$leaf_terms = array_values(
		array_filter(
			$terms,
			static function ( $term ) {
				return 0 !== (int) $term->parent && 'uncategorized' !== $term->slug;
			}
		)
	);

	if ( empty( $leaf_terms ) ) {
		$leaf_terms = array_values(
			array_filter(
				$terms,
				static function ( $term ) {
					return 'uncategorized' !== $term->slug;
				}
			)
		);
	}

	usort(
		$leaf_terms,
		static function ( $a, $b ) {
			return strcmp( $a->name, $b->name );
		}
	);

	return $leaf_terms;
}

/**
 * Inject collection links as a sub-menu under the Shop nav item.
 *
 * Product collections (/collections/…) previously had no navigation path
 * from every page. This adds them as a dropdown under the existing Shop
 * menu entry in both header menus (desktop + handheld). Display-only:
 * nothing is written to the menu database, and WordPress automatically
 * gains the `menu-item-has-children` + `sub-menu` markup Storefront's
 * dropdown behaviour expects. Reflected in the footer "Explore" column too.
 */
add_filter( 'wp_nav_menu_objects', 'apexvalue_menu_collections', 20, 2 );
function apexvalue_menu_collections( $items, $args ) {
	// Header menus only (desktop primary + handheld), not the footer list.
	$is_header = in_array(
		$args->theme_location ?? '',
		array( 'primary', 'handheld', 'apexvalue-main' ),
		true
	);
	if ( ! $is_header ) {
		return $items;
	}

	// Flat lists (e.g. the footer menu with depth=1) don't take sub-menus.
	if ( isset( $args->depth ) && 1 === (int) $args->depth ) {
		return $items;
	}

	$terms = apexvalue_get_collections();
	if ( empty( $terms ) ) {
		return $items;
	}

	// Find the top-level Shop item (WooCommerce shop page).
	$shop_key = null;
	foreach ( $items as $key => $item ) {
		if ( 0 === (int) $item->menu_item_parent
			&& 'post_type' === $item->type && 'page' === $item->object
			&& function_exists( 'wc_get_page_id' )
			&& (int) $item->object_id === (int) wc_get_page_id( 'shop' ) ) {
			$shop_key = $key;
			break;
		}
	}
	if ( null === $shop_key ) {
		return $items;
	}

	$shop_id = (int) $items[ $shop_key ]->ID;

	// _wp_menu_item_classes_by_context() runs BEFORE this filter, so the
	// injected items (and the Shop parent) must get their classes manually.
	if ( ! in_array( 'menu-item-has-children', (array) $items[ $shop_key ]->classes, true ) ) {
		$items[ $shop_key ]->classes[] = 'menu-item-has-children';
	}

	// Highlight the collection currently being browsed (category archives).
	$current_term_id = 0;
	if ( is_product_taxonomy() ) {
		$current_term_id = (int) get_queried_object_id();
	}

	// Build one WP_Post per collection term, parented under Shop.
	$children = array();
	foreach ( $terms as $term ) {
		$is_current = ( $current_term_id === (int) $term->term_id );
		$classes = array(
			'menu-item',
			'menu-item-type-taxonomy',
			'menu-item-object-product_cat',
		);
		if ( $is_current ) {
			$classes[] = 'current-menu-item';
		}
		$children[] = new WP_Post(
			(object) array(
				'ID'               => 100000 + (int) $term->term_id,
				'post_type'        => 'nav_menu_item',
				'post_status'      => 'publish',
				'post_title'       => $term->name,
				// The nav walker renders ->title, not ->post_title; leaving
				// this unset produced clickable but EMPTY menu links (a11y
				// "link-name" failure + invisible dropdown rows).
				'title'            => $term->name,
				'db_id'            => 0,
				'menu_item_parent' => $shop_id,
				'type'             => 'taxonomy',
				'object'           => 'product_cat',
				'object_id'        => $term->term_id,
				'url'              => get_term_link( $term ),
				'classes'          => $classes,
				'xfn'              => '',
				'current'          => $is_current,
				'current_item_ancestor' => false,
				'current_item_parent'   => false,
			)
		);
	}

	// Mark the Shop parent as an ancestor while a collection archive is open
	// (WordPress only handles this for DB-backed menu items), so the desktop
	// nav keeps its active colour on /collections/… pages.
	if ( $current_term_id ) {
		$shop_classes = (array) $items[ $shop_key ]->classes;
		foreach ( array( 'current-menu-ancestor', 'current-menu-parent' ) as $cls ) {
			if ( ! in_array( $cls, $shop_classes, true ) ) {
				$items[ $shop_key ]->classes[] = $cls;
			}
		}
	}

	// Rebuild the list with children directly after the Shop item.
	$new_items = array();
	foreach ( $items as $key => $item ) {
		$new_items[ $key ] = $item;
		if ( $key === $shop_key ) {
			foreach ( $children as $child ) {
				$new_items[] = $child;
			}
		}
	}

	return $new_items;
}


/**
 * Image for a collection card: the first product's thumbnail for the term,
 * cached in a transient (1 h). Returns the term's own thumbnail when set.
 *
 * @param WP_Term $term Product category term.
 * @return string|null Attachment image URL, or null when the collection has
 *                     no images (callers render a monogram fallback).
 */
function apexvalue_collection_image( $term ) {
	$cache_key = 'apexvalue_coll_img_' . (int) $term->term_id;
	$cached = get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached;
	}

	$url = null;

	// 1. Term thumbnail, if the shop manager ever sets one.
	$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( $thumb_id ) {
		$src = wp_get_attachment_image_src( $thumb_id, 'woocommerce_thumbnail' );
		if ( $src ) {
			$url = $src[0];
		}
	}

	// 2. Fallback: newest product image in the collection.
	if ( ! $url ) {
		$products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 1,
				'category' => array( $term->slug ),
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);
		if ( $products ) {
			$image_id = (int) $products[0]->get_image_id();
			if ( $image_id ) {
				$src = wp_get_attachment_image_src( $image_id, 'woocommerce_thumbnail' );
				if ( $src ) {
					$url = $src[0];
				}
			}
		}
	}

	set_transient( $cache_key, $url, HOUR_IN_SECONDS );

	return $url;
}

/**
 * Homepage collections strip.
 *
 * Renders directly under the hero on the front page: a "Shop by collection"
 * row that links every product collection (the same term set as the shop
 * chips and the Shop dropdown). Hides itself automatically when no
 * collections exist.
 */
function apexvalue_home_collections() {
	if ( ! is_front_page() || ! function_exists( 'wc_get_page_id' ) ) {
		return;
	}

	$terms = apexvalue_get_collections();
	if ( empty( $terms ) ) {
		return;
	}

	$shop_url = get_permalink( (int) wc_get_page_id( 'shop' ) );
	?>
	<section class="apex-collections" aria-label="<?php esc_attr_e( 'Browse collections', 'apexvalue' ); ?>">
		<div class="col-full apex-collections__inner">
			<div class="apex-collections__head">
				<h2 class="apex-collections__title"><?php esc_html_e( 'Shop by collection', 'apexvalue' ); ?></h2>
				<?php if ( $shop_url ) : ?>
					<a class="apex-collections__all" href="<?php echo esc_url( $shop_url ); ?>">
						<?php esc_html_e( 'View all products', 'apexvalue' ); ?><span aria-hidden="true">&nbsp;&rarr;</span>
					</a>
				<?php endif; ?>
			</div>
			<ul class="apex-collections__grid">
				<?php foreach ( $terms as $term ) : ?>
				<?php $img_url = apexvalue_collection_image( $term ); ?>
				<li class="apex-collections__item">
					<a class="apex-collections__card" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
						<span class="apex-collections__media" aria-hidden="true">
							<?php if ( $img_url ) : ?>
								<img src="<?php echo esc_url( $img_url ); ?>" alt="" loading="lazy" decoding="async" />
							<?php else : ?>
								<span class="apex-collections__mono"><?php echo esc_html( mb_substr( $term->name, 0, 1 ) ); ?></span>
							<?php endif; ?>
						</span>
						<span class="apex-collections__name"><?php echo esc_html( $term->name ); ?></span>
							<span class="apex-collections__count">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: number of products in the collection. */
										_n( '%s product', '%s products', $term->count, 'apexvalue' ),
										number_format_i18n( $term->count )
									)
								);
								?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
}
add_action( 'storefront_before_content', 'apexvalue_home_collections', 30 );

/**
 * Suppress the blog sidebar (Recent Posts / Archives / Categories widgets) on
 * transactional WooCommerce pages. A quotation-first store has no reason to
 * cross-link blog widgets inside the cart and account flows; those pages get
 * the full content width instead.
 *
 * Pluggable override: the child theme's functions.php loads before Storefront's,
 * so this definition wins over storefront_get_sidebar() without removing hooks.
 */
function storefront_get_sidebar() {
	if ( function_exists( 'is_woocommerce' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		return;
	}
	get_sidebar();
}

/**
 * Name image-only links inside raw post/page HTML.
 *
 * The homepage collection strip is hand-authored HTML: <a><img alt=""></a>
 * with no link text. axe (link-name) treats every one as a serious
 * failure for screen-reader users. Fill the accessibility name from the
 * linked tag archive title (fallback: the image's attachment alt/caption,
 * then the filename) without touching the visible design.
 */
add_filter( 'render_block', 'apexvalue_name_image_links', 10, 2 );
function apexvalue_name_image_links( $block_content, $block ) {
	if ( is_admin() || wp_doing_ajax() || empty( $block_content ) ) {
		return $block_content;
	}

	// Cheap pre-check before firing a regex over every block.
	if ( false === strpos( $block_content, '<a' ) || false === strpos( $block_content, '<img' ) ) {
		return $block_content;
	}

	return preg_replace_callback(
		'#<a\b([^>]*)>\s*<img\b([^>]*)>\s*</a>#is',
		function ( $m ) {
			// Skip anchors that already have an accessible name.
			if ( preg_match( '/aria-label="/', $m[1] ) || preg_match( '/alt="[^"]+"/', $m[2] ) ) {
				return $m[0];
			}

			$name = '';
			$src  = '';

			// Preferred: the linked URL's slug reads like a human label
			// ("Vantage Vue"), whereas attachment titles are often raw
			// filenames ("collection_vantage-vue_1024x1024").
			if ( preg_match( '/href="([^"]+)"/', $m[1], $h ) ) {
				$path = trim( (string) parse_url( $h[1], PHP_URL_PATH ), '/' );
				$slug = $path ? basename( $path ) : '';
				if ( $slug ) {
					$name = ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
				}
			}

			if ( '' === $name && preg_match( '/src="([^"]+)"/', $m[2], $s ) ) {
				$src = $s[1];
				$att = attachment_url_to_postid( $src );
				if ( $att ) {
					$name = get_the_title( $att );
				}
			}

			if ( '' === $name && $src ) {
				$name = ucwords( str_replace( array( '-', '_' ), ' ', pathinfo( (string) parse_url( $src, PHP_URL_PATH ), PATHINFO_FILENAME ) ) );
			}
			if ( '' === $name ) {
				return $m[0];
			}

			$label = esc_attr( $name );
			return sprintf(
				'<a%1$s aria-label="%2$s"><img%3$s></a>',
				$m[1],
				$label,
				$m[2]
			);
		},
		$block_content
	);
}
