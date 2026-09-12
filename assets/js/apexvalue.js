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
	 * ------------------------------------------------------------------ */
	var revealEls = d.querySelectorAll( '.apex-reveal' );

	if ( revealEls.length && 'IntersectionObserver' in window && ! reduceMotion ) {
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-visible' );
						observer.unobserve( entry.target );
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
