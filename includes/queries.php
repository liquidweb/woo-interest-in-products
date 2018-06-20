<?php
/**
 * Basic data queries used within the plugin.
 *
 * @package WooSubscribeToProducts
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProducts\Queries;

// Set our aliases.
use LiquidWeb\WooSubscribeToProducts as Core;
use LiquidWeb\WooSubscribeToProducts\Helpers as Helpers;

/**
 * Get all the product IDs that have the optin.
 *
 * @return array
 */
function get_enabled_products() {

	// Call the global database.
	global $wpdb;

	// Set up our query.
	$setup  = $wpdb->prepare("
		SELECT   post_id
		FROM     $wpdb->postmeta
		WHERE    meta_key = '%s'
	", esc_sql( Core\PROD_META_KEY ) );

	// Process the query.
	$query  = $wpdb->get_col( $setup );

	// If we came back false, return the error.
	if ( ! $query ) {

		// Return the WP_Error item if we have it, otherwise a generic false.
		return ! $wp_error ? false : new WP_Error( 'db_query_error', __( 'Could not execute query', 'woo-subscribe-to-products' ), $wpdb->last_error );
	}

	// Return the array of product IDs, filtering out the duplicates.
	return array_unique( $query );
}
