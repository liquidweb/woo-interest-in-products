
/**
 * Scroll up to our message text.
 */
function scrollToMessage() {

	jQuery( 'html,body' ).animate({
		scrollTop: jQuery( '.lw-woo-gdpr-user-optins-account-notices' ).offset().top - 60
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
	$( 'form.lw-woo-gdpr-user-optins-change-form' ).submit( function( event ) {

		// Stop the actual click.
		event.preventDefault();

		// Clear any existing notices.
		$( '.lw-woo-gdpr-user-optins-account-notices' ).find( '.lw-woo-gdpr-user-optins-notice' ).remove();

		// Fetch the nonce.
		var optsNonce   = document.getElementById( 'lw_woo_gdpr_user_optins_change_nonce' ).value;

		// Bail real quick without a nonce.
		if ( '' === optsNonce || undefined === optsNonce ) {
			return false;
		}

		// Get our choices made.
		var optsChoices = $( 'ul.lw-woo-gdpr-user-optins-items-list-wrap input:checked' ).map( function() {
			return this.id;
		}).get();

		// Build the data structure for the call.
		var data = {
			action: 'lw_woo_gdpr_save_user_optins',
			user_id: document.getElementById( 'lw_woo_gdpr_user_optins_change_user_id' ).value,
			optins: optsChoices,
			nonce: optsNonce
		};

		// Send out the ajax call itself.
		jQuery.post( frontWooUserGDPR.ajaxurl, data, function( response ) {

			// Handle the notice on it's own.
			if ( response.data.notice !== '' ) {

				// Add the message.
				$( '.lw-woo-gdpr-user-optins-account-notices' ).html( response.data.notice );

				// And scroll up to it.
				scrollToMessage();
			}

			// No error, save our items.
			if ( response.success === true || response.success === 'true' ) {

				// Handle loading the markup.
				if ( response.data.markup !== '' ) {
					$( 'ul.lw-woo-gdpr-user-optins-items-list-wrap' ).empty().append( response.data.markup );
				}
			}

		}, 'json' );
	});

	/**
	 * Handle the notice dismissal.
	 */
	$( '.lw-woo-gdpr-user-optins-account-notices' ).on( 'click', '.lw-woo-gdpr-user-optins-notice-dismiss', function( event ) {

		// Stop the actual click.
		event.preventDefault();

		// Now fade out the message, then remove it.
		$( '.lw-woo-gdpr-user-optins-notice' ).fadeOut( 'slow', function() {
			$( this ).remove();
		});
	});

//********************************************************
// You're still here? It's over. Go home.
//********************************************************
});
