/**
 * Apex Value front-end interactions.
 *
 * Small, dependency-free enhancements (~2 KB):
 *  - light/dark mode toggle (auto-detects the OS, persists the choice)
 *  - elevated header shadow once the page scrolls
 *  - scroll-reveal for elements tagged .apex-reveal
 *  - cart-count "bump" when the AJAX fragments refresh
 *  - smooth same-page anchor scrolling (keeps keyboard focus correct)
 *
 * Everything is progressive: without JavaScript the site renders fully
 * styled (mode follows the OS via CSS alone), and `prefers-reduced-motion`
 * is respected (CSS disables the transitions; the JS checks it before any
 * programmatic scrolling).
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

/**
 * Light/dark mode toggle.
 *
 * Default = AUTO: the CSS media query alone follows the OS. The toggle
 * pins a mode (html[data-apx-theme]) and persists it; while unpinned the
 * site keeps following live OS changes. The head bootstrap script (PHP)
 * has already applied the pre-paint state — this only adds interaction:
 * sync button state/labels, update meta theme-color, and toggle.
 */
( function () {
	'use strict';

	var d = document;
	var root = d.documentElement;
	var KEY = 'apexvalue:mode';
	var mql = window.matchMedia ? window.matchMedia( '(prefers-color-scheme: dark)' ) : null;

	function effective() {
		var pinned = root.getAttribute( 'data-apx-theme' );
		if ( pinned === 'light' || pinned === 'dark' ) {
			return pinned;
		}
		return mql && mql.matches ? 'dark' : 'light';
	}

	function sync() {
		var dark = effective() === 'dark';
		d.querySelectorAll( '.apex-mode-toggle' ).forEach( function ( btn ) {
			btn.setAttribute( 'aria-pressed', dark ? 'true' : 'false' );
			btn.setAttribute(
				'aria-label',
				dark ? 'Switch to light mode' : 'Switch to dark mode'
			);
		} );
		var meta = d.querySelector( 'meta[name="theme-color"]' );
		if ( meta ) {
			meta.setAttribute( 'content', dark ? '#101418' : '#ffffff' );
		}
	}

	var animTimer;

	function onClick() {
		var next = effective() === 'dark' ? 'light' : 'dark';

		// Cross-fade the surfaces (~300ms): add the anim class, force one
		// reflow so the transition is registered in the before-change style,
		// then flip. The class is removed shortly after, so it never exists
		// on initial page load — no fade flash on ordinary views.
		root.classList.add( 'apex-mode-anim' );
		void root.offsetHeight; // force style/layout flush
		root.setAttribute( 'data-apx-theme', next );

		try {
			localStorage.setItem( KEY, next );
		} catch ( e ) {
			/* Storage unavailable (private mode) — pin for this pageview only. */
		}

		clearTimeout( animTimer );
		animTimer = setTimeout( function () {
			root.classList.remove( 'apex-mode-anim' );
		}, 450 );

		sync();
	}

	function bind() {
		d.querySelectorAll( '.apex-mode-toggle' ).forEach( function ( btn ) {
			if ( ! btn.dataset.apxModeBound ) {
				btn.dataset.apxModeBound = '1';
				btn.addEventListener( 'click', onClick );
			}
		} );
		sync();
	}

	// While unpinned (auto), follow live OS changes.
	if ( mql ) {
		var onChange = function () {
			if ( ! root.getAttribute( 'data-apx-theme' ) ) {
				sync();
			}
		};
		if ( mql.addEventListener ) {
			mql.addEventListener( 'change', onChange );
		} else if ( mql.addListener ) {
			mql.addListener( onChange ); // Safari < 14
		}
	}

	if ( d.readyState === 'loading' ) {
		d.addEventListener( 'DOMContentLoaded', bind );
	} else {
		bind();
	}
} )();

/* ------------------------------------------------------------------
 * 6. Collections carousel (progressive enhancement)
 *
 * The collections strip is a plain grid without JS. With JS, it
 * becomes a scroll-snap rail with prev/next buttons. Buttons stay
 * `hidden` until JS confirms the rail actually overflows.
 * ------------------------------------------------------------------ */
( function () {
	var track = document.getElementById( 'apexCollectionsTrack' );

	if ( ! track ) {
		return;
	}

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var viewport = track.closest( '.apex-collections__viewport' );
	var section = track.closest( '.apex-collections' );
	var prev = section ? section.querySelector( '.apex-collections__nav-btn--prev' ) : null;
	var next = section ? section.querySelector( '.apex-collections__nav-btn--next' ) : null;

	if ( ! viewport || ! prev || ! next ) {
		return;
	}

	viewport.classList.add( 'apex-collections__viewport--scroll' );

	var updateButtons = function () {
		var maxScroll = track.scrollWidth - track.clientWidth;
		prev.disabled = track.scrollLeft <= 2;
		next.disabled = track.scrollLeft >= maxScroll - 2;
		var overflows = maxScroll > 2;
		prev.hidden = ! overflows;
		next.hidden = ! overflows;
	};

	var scrollByPage = function ( dir ) {
		track.scrollBy( {
			left: dir * track.clientWidth * 0.8,
			behavior: reduceMotion ? 'auto' : 'smooth'
		} );
	};

	prev.addEventListener( 'click', function () {
		scrollByPage( -1 );
	} );
	next.addEventListener( 'click', function () {
		scrollByPage( 1 );
	} );

	var ticking = false;
	track.addEventListener(
		'scroll',
		function () {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( function () {
					updateButtons();
					ticking = false;
				} );
			}
		},
		{ passive: true }
	);

	window.addEventListener( 'resize', updateButtons );
	updateButtons();
} )();

/* ------------------------------------------------------------------
 * 7. Back-to-top button
 * ------------------------------------------------------------------ */
( function () {
	var btn = document.querySelector( '.apex-top' );

	if ( ! btn ) {
		return;
	}

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var ticking = false;

	var update = function () {
		var show = window.scrollY > 600;
		if ( show && btn.hidden ) {
			btn.hidden = false;
		}
		btn.classList.toggle( 'apex-top--show', show );
		ticking = false;
	};

	window.addEventListener(
		'scroll',
		function () {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		},
		{ passive: true }
	);

	btn.addEventListener( 'click', function () {
		window.scrollTo( {
			top: 0,
			behavior: reduceMotion ? 'auto' : 'smooth'
		} );
	} );

	update();
} )();
