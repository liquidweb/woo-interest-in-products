<?php
/**
 * Our helper functions to use across the plugin.
 *
 * @package WooSubscribeToProducts
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProducts\Helpers;

// Set our aliases.
use LiquidWeb\WooSubscribeToProducts as Core;

/**
 * Manage saving the opt-in choices for a user.
 *
 * @param  integer $user_id   The user we are going to look up if no customer object is there.
 * @param  object  $customer  The customer object.
 * @param  array   $products  The field data to use in updating.
 *
 * @return boolean
 */
function update_user_product_subscriptions( $user_id = 0, $customer, $products = array() ) {

	// Make sure we have everything required.
	if ( empty( $user_id ) && empty( $customer ) ) {
		return false;
	}

	// @@todo what to do

	// And just be done.
	return true;
}
