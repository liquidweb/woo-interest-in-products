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
 * Get all the product IDs that have the optin.
 *
 * @return array
 */
function get_products_with_optins() {

	// Call the global database.
	global $wpdb;

	// Set my table name to use.
	$table  = $wpdb->prefix . 'postmeta';

	// Set up our query.
	$setup  = $wpdb->prepare("
		SELECT   post_id
		FROM     $table
		WHERE    meta_key = '%s'
	", esc_sql( Core\PROD_META_KEY ) );

	// Process the query.
	$query  = $wpdb->get_col( $setup );

	// Return the array of message IDs, or false.
	return ! empty( $query ) ? $query : false;
}

/**
 * Manage saving the opt-in choices for a user.
 *
 * @param  integer $user_id   The user we are going to look up if no customer object is there.
 * @param  object  $customer  The customer object.
 * @param  array   $products  The field data to use in updating.
 *
 * @return boolean
 */
function update_user_product_subscribe( $user_id = 0, $customer, $products = array() ) {

	// Make sure we have everything required.
	if ( empty( $user_id ) && empty( $customer ) ) {
		return false;
	}

	// @@todo what to do

	// And just be done.
	return true;
}
