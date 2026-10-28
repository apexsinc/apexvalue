/**
 * Live preview for the Apex Value hero in the Customizer.
 *
 * Mirrors the sanitisation behaviour of apexvalue_hero_render() — a field
 * whose trimmed value is empty renders nothing at all.
 */
( function ( api, $ ) {
	if ( ! api || ! $ ) {
		return;
	}

	/**
	 * Bind one setting to one element.
	 *
	 * @param {string} setting  Customizer setting ID.
	 * @param {string} selector CSS selector of the target element.
	 * @param {string} attr     'text' to swap text content, or an attribute
	 *                          name (e.g. 'href') to swap that attribute.
	 */
	function bindField( setting, selector, attr ) {
		api( setting, function ( value ) {
			value.bind( function ( to ) {
				var $el = $( selector );

				if ( ! $el.length ) {
					return;
				}

				if ( '' === String( to ).trim() ) {
					$el.hide();
					return;
				}

				if ( 'text' === attr ) {
					$el.text( to );
				} else {
					$el.attr( attr, to );
				}

				$el.show();
			} );
		} );
	}

	bindField( 'apexvalue_hero_kicker', '.apex-hero__kicker', 'text' );
	bindField( 'apexvalue_hero_headline', '.apex-hero__title', 'text' );
	bindField( 'apexvalue_hero_text', '.apex-hero__text', 'text' );
	bindField( 'apexvalue_hero_cta_text', '.apex-hero__btn--primary', 'text' );
	bindField( 'apexvalue_hero_cta_url', '.apex-hero__btn--primary', 'href' );
	bindField( 'apexvalue_hero_cta2_text', '.apex-hero__btn--ghost', 'text' );
	bindField( 'apexvalue_hero_cta2_url', '.apex-hero__btn--ghost', 'href' );
} )( window.wp && window.wp.customize, window.jQuery );
