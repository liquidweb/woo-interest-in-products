
/**
 * Now let's get started.
 */
jQuery( document ).ready( function($) {

	/**
	 * Clear the new field inputs.
	 */
	function clearNewFieldInputs() {

		// Set the new input row as a variable.
		var $newFields  = $( 'tr.lw-woo-gdpr-user-optins-new-fields-row' );

		// Set the icon.
		var $iconCheck  = $newFields.find( 'span.lw-woo-gdpr-user-optins-field-new-success' );

		// Uncheck the box.
		$newFields.find( '#lw-woo-gdpr-user-optin-required-new' ).prop( 'checked', false );

		// Handle the text input fields.
		$newFields.find( '#lw-woo-gdpr-user-optin-title-new' ).val( '' ).focus();
		$newFields.find( '#lw-woo-gdpr-user-optin-label-new' ).val( '' );

		// Remove the class from the icon to display it.
		$iconCheck.removeClass( 'lw-woo-gdpr-user-optins-field-hidden' );

		// Then hide it again.
		hideAgain = setTimeout( function() {
			$iconCheck.addClass( 'lw-woo-gdpr-user-optins-field-hidden' );
		}, 3000 );
	}

	// Set our button variable to false.
	var saveSubmit  = false;

	/**
	 * Set some object vars for later.
	 */
	var $tabBody    = $( 'body.lw-woo-gdpr-user-optins-admin-tab' );
	var $sortTable  = $( 'table.lw-woo-gdpr-user-optins-list-table-wrap' );
	var $sortBody   = $( 'table.lw-woo-gdpr-user-optins-list-table-wrap tbody' );
	var $mainForm   = $( 'body.lw-woo-gdpr-user-optins-admin-tab form#mainform' );

	/**
	 * Set up the sortable table rows.
	 */
	if ( $sortTable.length > 0 ) {

		// Make our table sortable.
		$sortBody.sortable({
			handle: '.lw-woo-gdpr-user-optins-field-trigger-icon',
			containment: $sortTable,
			update: function( event, ui ) {

				// Build the data structure for the call with the updated sort order.
				var data = {
					action: 'lw_woo_gdpr_optins_sort',
					sorted: $sortBody.sortable( 'toArray', { attribute: 'data-key' } )
				};

				// Send the post request, we don't actually care about the response.
				jQuery.post( ajaxurl, data );
			},
		});
	}

	// Don't even think about running this anywhere else.
	if ( $tabBody.length > 0 ) {

		/**
		 * Set the button variable to handle the two submits.
		 */
		$mainForm.on( 'click', 'button', function() {
			saveSubmit = $( this ).hasClass( 'lw-woo-gdpr-user-optin-add-new-button' );
		});

		/**
		 * Add a new item into the table.
		 */
		$mainForm.submit( function( event ) {

			// Bail on the actual save button.
			if ( saveSubmit !== true ) {
				return;
			}

			// Stop the actual submit.
			event.preventDefault();

			// We call this a sledgehammer because Woo doesn't register
			// the callback until the user has clicked one of the tabs.
			$( '.woo-nav-tab-wrapper a' ).off();

			// Fetch the nonce.
			var newNonce = document.getElementById( 'lw_woo_gdpr_new_optin_nonce' ).value;

			// Bail real quick without a nonce.
			if ( '' === newNonce || undefined === newNonce ) {
				return false;
			}

			// Build the data structure for the call.
			var data = {
				action: 'lw_woo_gdpr_optins_add_new',
				required: $( 'input#lw-woo-gdpr-user-optin-required-new' ).is( ':checked' ),
				title: document.getElementById( 'lw-woo-gdpr-user-optin-title-new' ).value,
				label: document.getElementById( 'lw-woo-gdpr-user-optin-label-new' ).value,
				nonce: newNonce
			};

			// Send out the ajax call itself.
			jQuery.post( ajaxurl, data, function( response ) {

				// Refresh the sortable table.
				$sortBody.sortable( 'refreshPositions' );

				// Handle the failure.
				if ( response.success !== true ) {

					// Set our message if we have one.
					if ( undefined !== response.data && undefined !== response.data.notice && '' !== response.data.notice ) {
						$tabBody.find( '.woocommerce h1:first' ).after( response.data.notice );
					}

					// And just bail.
					return false;
				}

				// We got table row markup, so show it.
				if ( undefined !== response.data && undefined !== response.data.markup && '' !== response.data.markup ) {

					// Clear the new field inputs.
					clearNewFieldInputs();

					// Add the row itself.
					$sortBody.find( 'tr:last' ).after( response.data.markup );
				}
			}, 'json' );
		});

		/**
		 * Handle the individual item deletion.
		 */
		$sortBody.on( 'click', 'a.lw-woo-gdpr-user-optins-field-trigger-trash', function( event ) {

			// Stop the actual click.
			event.preventDefault();

			// Set the initial var.
			var $this   = $( this );

			// Set my field block.
			var $fieldBlock = $this.parents( 'tr.lw-woo-gdpr-user-optins-single-row' );

			// Fetch my field ID and nonce.
			var fieldID     = $this.data( 'field-id' );
			var fieldNonce  = $this.data( 'nonce' );

			// Bail real quick without a nonce.
			if ( '' === fieldNonce || undefined === fieldNonce ) {
				return false;
			}

			// Handle the missing field ID.
			if ( '' === fieldID || undefined === fieldID ) {
				return false; // @@todo need a better return.
			}

			// Build the data structure for the call.
			var data = {
				action: 'lw_woo_gdpr_optins_delete_row',
				field_id: fieldID,
				nonce: fieldNonce
			};

			// Send out the ajax call itself.
			jQuery.post( ajaxurl, data, function( response ) {

				// Refresh the sortable table.
				$sortBody.sortable( 'refreshPositions' );

				// Handle the failure.
				if ( response.success !== true ) {

					// Set our message if we have one.
					if ( undefined !== response.data && undefined !== response.data.notice && '' !== response.data.notice ) {
						$tabBody.find( '.woocommerce h1:first' ).after( response.data.notice );
					}

					// And just bail.
					return false;
				}

				// No error, so remove the field.
				if ( response.success === true || response.success === 'true' ) {

					// Fade out the field and then remove it.
					$fieldBlock.fadeOut( 500, function() {
						$fieldBlock.remove();
					});
				}
			}, 'json' );
		});

		/**
		 * Handle the notice dismissal.
		 */
		$tabBody.on( 'click', '.notice-dismiss', function() {
			$tabBody.find( '.lw-woo-gdpr-user-optins-admin-message' ).remove();
		});

		// Nothing else here.
	}

//********************************************************
// You're still here? It's over. Go home.
//********************************************************
});
