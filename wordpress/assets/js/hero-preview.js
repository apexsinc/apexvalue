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

	bindField( 'apexvalue_hero_kicker', '.apex-hero__kicker-text', 'text' );
	bindField( 'apexvalue_hero_text', '.apex-hero__text', 'text' );
	bindField( 'apexvalue_hero_cta_text', '.apex-hero__btn--primary', 'text' );
	bindField( 'apexvalue_hero_cta_url', '.apex-hero__btn--primary', 'href' );
	bindField( 'apexvalue_hero_cta2_text', '.apex-hero__btn--ghost', 'text' );
	bindField( 'apexvalue_hero_cta2_url', '.apex-hero__btn--ghost', 'href' );

	/**
	 * Headline + accent render together: the accent phrase is wrapped in
	 * <em class="apex-hero__accent">, mirroring apexvalue_hero_render().
	 */
	function renderHeadline() {
		var $title = $( '.apex-hero__title' );

		if ( ! $title.length ) {
			return;
		}

		var headline = String( api( 'apexvalue_hero_headline' )() );
		var accent = String( api( 'apexvalue_hero_accent' )() ).trim();

		if ( '' === headline.trim() ) {
			$title.hide();
			return;
		}

		var escaped = $( '<span>' ).text( headline ).html();
		var escapedAccent = $( '<span>' ).text( accent ).html();

		if ( '' !== accent && -1 !== headline.indexOf( accent ) ) {
			escaped = escaped.split( escapedAccent ).join(
				'<em class="apex-hero__accent">' + escapedAccent + '</em>'
			);
		}

		$title.html( escaped ).show();
	}

	api( 'apexvalue_hero_headline', function ( value ) {
		value.bind( renderHeadline );
	} );
	api( 'apexvalue_hero_accent', function ( value ) {
		value.bind( renderHeadline );
	} );
} )( window.wp && window.wp.customize, window.jQuery );
