/**
 * Apex Value front-end interactions.
 *
 * Small, dependency-free enhancements (~2 KB):
 *  - elevated header shadow once the page scrolls
 *  - scroll-reveal for elements tagged .apex-reveal
 *  - cart-count "bump" when the AJAX fragments refresh
 *  - smooth same-page anchor scrolling (keeps keyboard focus correct)
 *
 * Everything is progressive: without JavaScript the site renders fully
 * styled, and `prefers-reduced-motion` is respected (CSS disables the
 * transitions; the JS checks it before any programmatic scrolling).
 */
( function () {
	'use strict';

	var d = document;
	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	// Tag <html> so CSS only hides reveal elements when JS is guaranteed
	// to run (progressive enhancement failsafe).
	d.documentElement.classList.add( 'apex-js' );

	/* ------------------------------------------------------------------
	 * 1. Header elevation on scroll
	 * ------------------------------------------------------------------ */
	var header = d.querySelector( '.site-header' );

	if ( header ) {
		var ticking = false;

		var updateHeader = function () {
			header.classList.toggle( 'apex-header--scrolled', window.scrollY > 8 );
			ticking = false;
		};

		window.addEventListener(
			'scroll',
			function () {
				if ( ! ticking ) {
					ticking = true;
					window.requestAnimationFrame( updateHeader );
				}
			},
			{ passive: true }
		);

		updateHeader();
	}

	/* ------------------------------------------------------------------
	 * 2. Scroll reveal (IntersectionObserver, once per element)
	 *
	 * Column groups and product grids are auto-tagged here so their
	 * children reveal as a cascade (delay via data-reveal-delay).
	 * ------------------------------------------------------------------ */
	var cascadeGroups = d.querySelectorAll(
		'.wp-block-columns, ul.products, .wc-block-grid__products, .wc-block-product-template'
	);

	Array.prototype.forEach.call( cascadeGroups, function ( group ) {
		var items = group.querySelectorAll(
			':scope > .wp-block-column, :scope > li.product, :scope > .wc-block-grid__product'
		);

		Array.prototype.forEach.call( items, function ( item, i ) {
			if ( item.classList.contains( 'apex-reveal' ) ) {
				return;
			}
			item.classList.add( 'apex-reveal' );
			item.setAttribute( 'data-reveal-delay', String( ( i % 4 ) + 1 ) );
		} );
	} );

	var revealEls = d.querySelectorAll( '.apex-reveal' );

	if ( revealEls.length && 'IntersectionObserver' in window && ! reduceMotion ) {
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						var el = entry.target;
						el.classList.add( 'is-visible' );
						observer.unobserve( el );

						// Once the staggered reveal has landed, clear the
						// delay so hover transitions stay instant.
						var delay = parseInt( el.getAttribute( 'data-reveal-delay' ), 10 );
						delay = isNaN( delay ) ? 0 : delay;
						window.setTimeout(
							function () {
								el.style.transitionDelay = '0s';
							},
							delay * 80 + 700
						);
					}
				} );
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.05 }
		);

		Array.prototype.forEach.call( revealEls, function ( el ) {
			observer.observe( el );
		} );
	} else {
		// No IO support or reduced motion: show everything immediately.
		Array.prototype.forEach.call( revealEls, function ( el ) {
			el.classList.add( 'is-visible' );
		} );
	}

	/* ------------------------------------------------------------------
	 * 3. Cart count bump on AJAX fragment refresh
	 * ------------------------------------------------------------------ */
	if ( window.jQuery && jQuery( document ).on ) {
		jQuery( document.body ).on( 'wc_fragments_refreshed wc_fragments_loaded', function () {
			var count = d.querySelector( '.site-header-cart .count' );
			if ( ! count ) {
				return;
			}
			count.classList.remove( 'apex-bump' );
			// Force a reflow so the animation can replay.
			void count.offsetWidth;
			count.classList.add( 'apex-bump' );
		} );
	}

	/* ------------------------------------------------------------------
	 * 4. Smooth same-page anchors (focus-correct, reduced-motion aware)
	 * ------------------------------------------------------------------ */
	d.addEventListener(
		'click',
		function ( event ) {
			if ( reduceMotion || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
				return;
			}

			var link = event.target.closest ? event.target.closest( 'a[href^="#"]' ) : null;
			if ( ! link ) {
				return;
			}

			var id = link.getAttribute( 'href' );
			if ( '#' === id || id.length < 2 ) {
				return;
			}

			var target = d.getElementById( id.slice( 1 ) );
			if ( ! target ) {
				return;
			}

			event.preventDefault();
			target.scrollIntoView( { behavior: 'smooth', block: 'start' } );

			// Keep keyboard focus in step with the visual position.
			target.setAttribute( 'tabindex', '-1' );
			target.focus( { preventScroll: true } );
			history.replaceState( null, '', id );
		},
		false
	);
} )();

/**
 * Name third-party carousel controls that ship without accessible labels
 * (Google Reviews widget prev/next). Progressive: a no-op if absent.
 */
( function () {
	'use strict';

	var names = [ [ 'grw-prev', 'Previous reviews' ], [ 'grw-next', 'Next reviews' ] ];
	names.forEach( function ( pair ) {
		document.querySelectorAll( 'button.' + pair[ 0 ] ).forEach( function ( btn ) {
			if ( ! btn.hasAttribute( 'aria-label' ) ) {
				btn.setAttribute( 'aria-label', pair[ 1 ] );
			}
		} );
	} );
} )();

/**
 * Make plugin scrollable regions keyboard-operable. Third-party widgets
 * (e.g. the Google Reviews carousel) render overflow-scroll containers
 * without tabindex, leaving their content unreachable by keyboard — an
 * axe "scrollable-region-focusable" failure. Progressive: a no-op when
 * absent, and non-fatal if the page changes underneath us.
 */
( function () {
	'use strict';

	function fix() {
		document.querySelectorAll( '.wp-google-feedback.grw-scroll' ).forEach( function ( el ) {
			if ( ! el.hasAttribute( 'tabindex' ) ) {
				el.setAttribute( 'tabindex', '0' );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', fix );
	} else {
		fix();
	}
} )();

/**
 * WooCommerce shop pages render two pagination navs (top + bottom), both
 * labelled "Product Pagination" — an axe landmark-unique failure and a
 * screen-reader ambiguity. Differentiate them. Progressive: a no-op when
 * only one nav exists.
 */
( function () {
	'use strict';

	function fix() {
		var navs = document.querySelectorAll( 'nav.woocommerce-pagination' );
		if ( navs.length < 2 ) {
			return;
		}
		navs[ 0 ].setAttribute( 'aria-label', 'Product pagination (top)' );
		navs[ navs.length - 1 ].setAttribute( 'aria-label', 'Product pagination (bottom)' );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', fix );
	} else {
		fix();
	}
} )();

/**
 * An empty <h3> renders a heading level with no text (axe empty-heading,
 * minor). Hide it from the accessibility tree. Progressive: a no-op when
 * absent.
 */
( function () {
	'use strict';

	function fix() {
		document.querySelectorAll( 'h3' ).forEach( function ( h ) {
			if ( ! h.textContent.trim() && ! h.querySelector( 'img, svg, a' ) ) {
				h.setAttribute( 'aria-hidden', 'true' );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', fix );
	} else {
		fix();
	}
} )();
