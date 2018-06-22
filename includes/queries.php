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
 * Get all the product IDs that have the signup enabled.
 *
 * @param  boolean $flush  Whether to flush the cache first or not.
 *
 * @return array
 */
function get_enabled_products( $flush = false ) {

	// Set my transient key.
	$ky = 'woo_product_subscription_ids';

	// If we don't want the cache'd version, delete the transient first.
	if ( ! empty( $flush ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_transient( $ky );
	}

	// Check the transient.
	if ( false === $items = get_transient( $ky )  ) {

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
		set_transient( $ky, $items, HOUR_IN_SECONDS );
	}

	// Return the array of product IDs, filtering out the duplicates.
	return $items;
}

/**
 * Get the customers that have subscribed to a single product.
 *
 * @param  integer $product_id  The product ID to look up.
 * @param  boolean $flush       Whether to flush the cache first or not.
 *
 * @return array
 */
function get_customers_for_product( $product_id = 0, $flush = false ) {

	// Make sure we have a product ID.
	if ( empty( $product_id ) || 'product' !== get_post_type( $product_id ) ) {
		return new WP_Error( 'invalid_product_id', __( 'The required product ID is missing or invalid.', 'woo-subscribe-to-products' ) );
	}

	// Make sure we have an enabled product.
	if ( ! Helpers\maybe_product_enabled( $product_id ) ) {
		return new WP_Error( 'product_not_enabled', __( 'Subscriptions are not enabled for this product.', 'woo-subscribe-to-products' ) );
	}

	// Set my transient key.
	$ky = 'woo_product_subscribed_customers';

	// If we don't want the cache'd version, delete the transient first.
	if ( ! empty( $flush ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_transient( $ky );
	}

	// Check the transient.
	if ( false === $customers = get_transient( $ky )  ) {

		// Call the global database.
		global $wpdb;

		// Set up our query.
		$setup  = $wpdb->prepare("
			SELECT   user_id
			FROM     $wpdb->wc_product_subscriptions
			WHERE    product_id = '%d'
			ORDER BY created ASC
		", absint( $product_id ) );

		// Process the query.
		$query  = $wpdb->get_col( $setup );

		// If we came back false, return the error.
		if ( ! $query ) {

			// Return the WP_Error item if we have it, otherwise a generic false.
			return ! $wp_error ? false : new WP_Error( 'db_query_error', __( 'Could not execute query', 'woo-subscribe-to-products' ), $wpdb->last_error );
		}

		// Make sure they're unique.
		$customers  = array_unique( $query );

		// Set our transient with our data.
		set_transient( $ky, $customers, HOUR_IN_SECONDS );
	}

	// Return the array of user IDs, filtering out the duplicates.
	return $customers;
}

/**
 * Get the products that have subscribed by a customer.
 *
 * @param  integer $customer_id  The user ID to look up.
 * @param  boolean $flush        Whether to flush the cache first or not.
 *
 * @return array
 */
function get_products_for_user( $customer_id = 0, $flush = false ) {

	// Make sure we have a user ID.
	if ( empty( $customer_id ) ) {
		return new WP_Error( 'missing_customer_id', __( 'The required customer ID is missing.', 'woo-subscribe-to-products' ) );
	}

	// Set my transient key.
	$ky = 'woo_customer_subscribed_products';

	// If we don't want the cache'd version, delete the transient first.
	if ( ! empty( $flush ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_transient( $ky );
	}

	// Check the transient.
	if ( false === $products = get_transient( $ky )  ) {

		// Call the global database.
		global $wpdb;

		// Set up our query.
		$setup  = $wpdb->prepare("
			SELECT   product_id
			FROM     $wpdb->wc_product_subscriptions
			WHERE    user_id = '%d'
			ORDER BY created ASC
		", absint( $customer_id ) );

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
		set_transient( $ky, $products, HOUR_IN_SECONDS );
	}

	// Return the array of product IDs, filtering out the duplicates.
	return $products;
}
