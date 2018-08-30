
/**
 * Scroll up to our message text.
 */
function scrollToMessage() {

	jQuery( 'html,body' ).animate({
		scrollTop: jQuery( '.woo-product-interest-account-notice-wrap' ).offset().top - 60
	}, 500 );

	// And just return false.
	return false;
}

/**
 * Now let's get started.
 */
jQuery( document ).ready( function($) {

	/**
	 * Check for the user saving opt-in actions.
	 */
	$( 'form.woo-interest-in-products-change-form' ).submit( function( event ) {

		// Stop the actual click.
		event.preventDefault();

		// Clear any existing notices.
		$( '.woo-product-interest-account-notice-wrap' ).find( '.woo-product-interest-account-notice' ).remove();

		// Fetch the nonce.
		var interestNonce   = document.getElementById( 'wc_customer_interests_change_nonce' ).value;

		// Bail real quick without a nonce.
		if ( '' === interestNonce || undefined === interestNonce ) {
			return false;
		}

		// Get our choices made.
		var interestIDs = $( 'ul.woo-interest-in-products-list-wrap input:checked' ).map( function() {
			return this.value;
		}).get().join();

		// Build the data structure for the call.
		var data = {
			action: 'woo_update_customer_product_interest',
			customer_id: document.getElementById( 'wc_product_interest_customer_id' ).value,
			original_ids: document.getElementById( 'wc_product_interest_original_ids' ).value,
			interest_ids: interestIDs,
			nonce: interestNonce
		};

		// Send out the ajax call itself.
		jQuery.post( wooProductInterest.ajaxurl, data, function( response ) {

			// Handle the notice on it's own.
			if ( response.data.notice !== '' ) {

				// Add the message.
				$( '.woo-product-interest-account-notice-wrap' ).html( response.data.notice );

				// And scroll up to it.
				scrollToMessage();
			}

			// No error, save our items.
			if ( response.success === true || response.success === 'true' ) {

				// Handle loading the markup.
				if ( response.data.markup !== '' ) {
					$( 'ul.woo-interest-in-products-list-wrap' ).empty().append( response.data.markup );
				}

				// Handle loading the empty.
				if ( response.data.empty !== '' ) {
					$( 'div.woo-interest-in-products-change-form-wrapper' ).empty().append( response.data.empty );
				}
			}
		}, 'json' );
	});

	/**
	 * Handle the notice dismissal.
	 */
	$( '.woo-product-interest-account-notice-wrap' ).on( 'click', '.woo-product-interest-account-notice-dismiss', function( event ) {

		// Stop the actual click.
		event.preventDefault();

		// Now fade out the message, then remove it.
		$( '.woo-product-interest-account-notice' ).fadeOut( 'slow', function() {
			$( this ).remove();
		});
	});

//********************************************************
// You're still here? It's over. Go home.
//********************************************************
});
