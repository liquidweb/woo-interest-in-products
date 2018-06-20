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

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_transient( 'woo_product_subscription_ids' );
	}

	// Check the transient.
	if ( false === $items = get_transient( 'woo_product_subscription_ids' )  ) {

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

		// Make sure they're unique.
		$items  = array_unique( $query );

		// Set our transient with our data.
		set_transient( 'woo_product_subscription_ids', $items, HOUR_IN_SECONDS );
	}

	// Return the array of product IDs, filtering out the duplicates.
	return $items;
}

/**
 * Get the users that have subscribed to a single product.
 *
 * @param  integer $product_id  The product ID to look up.
 *
 * @return array
 */
function get_users_for_product( $product_id = 0 ) {

	// Make sure we have a product ID.
	if ( empty( $product_id ) || 'product' !== get_post_type( $product_id ) ) {
		return new WP_Error( 'invalid_product_id', __( 'The required product ID is missing or invalid.', 'woo-subscribe-to-products' ) );
	}

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_transient( 'woo_product_subscribed_users' );
	}

	// Check the transient.
	if ( false === $users = get_transient( 'woo_product_subscribed_users' )  ) {

		// Call the global database.
		global $wpdb;

		// Set up our query.
		$setup  = $wpdb->prepare("
			SELECT   user_id
			FROM     $wpdb->wc_product_subscriptions
			WHERE    product_id = '%d'
			ORDER BY created DESC
		", absint( $product_id ) );

		// Process the query.
		$query  = $wpdb->get_col( $setup );

		// If we came back false, return the error.
		if ( ! $query ) {

			// Return the WP_Error item if we have it, otherwise a generic false.
			return ! $wp_error ? false : new WP_Error( 'db_query_error', __( 'Could not execute query', 'woo-subscribe-to-products' ), $wpdb->last_error );
		}

		// Make sure they're unique.
		$users  = array_unique( $query );

		// Set our transient with our data.
		set_transient( 'woo_product_subscribed_users', $users, HOUR_IN_SECONDS );
	}

	// Return the array of user IDs, filtering out the duplicates.
	return $users;
}

/**
 * Get the products that have subscribed by a user.
 *
 * @param  integer $user_id  The user ID to look up.
 *
 * @return array
 */
function get_products_for_user( $user_id = 0 ) {

	// Make sure we have a user ID.
	if ( empty( $user_id ) ) {
		return new WP_Error( 'missing_user_id', __( 'The required user ID is missing.', 'woo-subscribe-to-products' ) );
	}

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_transient( 'woo_user_subscribed_products' );
	}

	// Check the transient.
	if ( false === $products = get_transient( 'woo_user_subscribed_products' )  ) {

		// Call the global database.
		global $wpdb;

		// Set up our query.
		$setup  = $wpdb->prepare("
			SELECT   product_id
			FROM     $wpdb->wc_product_subscriptions
			WHERE    user_id = '%d'
			ORDER BY created DESC
		", absint( $user_id ) );

		// Process the query.
		$query  = $wpdb->get_col( $setup );

		// If we came back false, return the error.
		if ( ! $query ) {

			// Return the WP_Error item if we have it, otherwise a generic false.
			return ! $wp_error ? false : new WP_Error( 'db_query_error', __( 'Could not execute query', 'woo-subscribe-to-products' ), $wpdb->last_error );
		}

		// Make sure they're unique.
		$products   = array_unique( $query );

		// Set our transient with our data.
		set_transient( 'woo_user_subscribed_products', $products, HOUR_IN_SECONDS );
	}

	// Return the array of product IDs, filtering out the duplicates.
	return $products;
}
