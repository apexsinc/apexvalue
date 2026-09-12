/* Customizer live preview for the Apex Value CTA band. */
( function ( api ) {
	api( 'apexvalue_cta_heading', function ( value ) {
		value.bind( function ( to ) {
			if ( ! to ) {
				jQuery( '.apexvalue-cta' ).css( 'display', 'none' );
				return;
			}
			jQuery( '.apexvalue-cta' ).css( 'display', '' );
			jQuery( '.apexvalue-cta__heading' ).text( to );
		} );
	} );

	api( 'apexvalue_cta_text', function ( value ) {
		value.bind( function ( to ) {
			var $p = jQuery( '.apexvalue-cta__text' );
			if ( ! to ) {
				$p.css( 'display', 'none' );
				return;
			}
			$p.css( 'display', '' ).text( to );
		} );
	} );

	api( 'apexvalue_cta_button_text', function ( value ) {
		value.bind( function ( to ) {
			var $a = jQuery( '.apexvalue-cta__button' );
			if ( ! to ) {
				$a.hide();
				return;
			}
			$a.show().text( to );
		} );
	} );

	api( 'apexvalue_cta_button_url', function ( value ) {
		value.bind( function ( to ) {
			jQuery( '.apexvalue-cta__button' ).attr( 'href', to );
		} );
	} );
} )( wp.customize );
