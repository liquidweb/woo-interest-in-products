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

		// If the query didn't work, handle it.
		if ( ! $query ) {

			// Return the WP_Error item if we have it, otherwise a generic false.
			if ( $wpdb->last_error ) {
				return new WP_Error( 'db_query_error', __( 'Could not execute query', 'woo-subscribe-to-products' ), $wpdb->last_error );
			} else {
				return false;
			}
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
	$ky = 'woo_product_subscribed_customers_' . absint( $product_id );

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
			SELECT   *
			FROM     $wpdb->wc_product_subscriptions
			WHERE    product_id = '%d'
			ORDER BY created ASC
		", absint( $product_id ) );

		// Process the query.
		$query  = $wpdb->get_results( $setup, ARRAY_A );

		// If we came back empty, check for an error return.
		if ( ! $query ) {

			// Return the WP_Error item if we have it, otherwise a generic false.
			if ( $wpdb->last_error ) {
				return new WP_Error( 'db_query_error', __( 'Could not execute query', 'woo-subscribe-to-products' ), $wpdb->last_error );
			} else {
				return false;
			}
		}

		// Make sure they're unique.
		$customers  = Helpers\sanitize_text_recursive( $query );

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
function get_products_for_customer( $customer_id = 0, $flush = false ) {

	// Make sure we have a user ID.
	if ( empty( $customer_id ) ) {
		return new WP_Error( 'missing_customer_id', __( 'The required customer ID is missing.', 'woo-subscribe-to-products' ) );
	}

	// Set my transient key.
	$ky = 'woo_customer_subscribed_products_' . absint( $customer_id );

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
			SELECT   *
			FROM     $wpdb->wc_product_subscriptions
			WHERE    customer_id = '%d'
			ORDER BY created ASC
		", absint( $customer_id ) );

		// Process the query.
		$query  = $wpdb->get_results( $setup, ARRAY_A );

		// If we came back false, return the error.
		if ( ! $query ) {

			// Return the WP_Error item if we have it, otherwise a generic false.
			if ( $wpdb->last_error ) {
				return new WP_Error( 'db_query_error', __( 'Could not execute query', 'woo-subscribe-to-products' ), $wpdb->last_error );
			} else {
				return false;
			}
		}

		// Make sure they're unique.
		$products   = Helpers\sanitize_text_recursive( $query );

		// Set our transient with our data.
		set_transient( $ky, $products, HOUR_IN_SECONDS );
	}

	// Return the array of product IDs, filtering out the duplicates.
	return $products;
}

/**
 * Get the product and customer ID from a relationship.
 *
 * @param  integer $relationship_id  The relationship ID tied to the subscription.
 *
 * @return void
 */
function get_data_by_relationship( $relationship_id = 0 ) {

	// Make sure we have a relationship ID.
	if ( empty( $relationship_id ) ) {
		return new WP_Error( 'missing_relationship_id', __( 'The required relationship ID is missing.', 'woo-subscribe-to-products' ) );
	}

	// Set my transient key.
	$ky = 'woo_customer_relationship_data_' . absint( $relationship_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( ! empty( $flush ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_transient( $ky );
	}

	// Check the transient.
	if ( false === $relationship = get_transient( $ky )  ) {

		// Call the global database.
		global $wpdb;

		// Set up our query.
		$setup  = $wpdb->prepare("
			SELECT   *
			FROM     $wpdb->wc_product_subscriptions
			WHERE    relationship_id = '%d'
			ORDER BY created ASC
		", absint( $relationship_id ) );

		// Process the query.
		$query  = $wpdb->get_row( $setup, ARRAY_A );

		// If we came back false, return the error.
		if ( ! $query ) {

			// Return the WP_Error item if we have it, otherwise a generic false.
			if ( $wpdb->last_error ) {
				return new WP_Error( 'db_query_error', __( 'Could not execute query', 'woo-subscribe-to-products' ), $wpdb->last_error );
			} else {
				return false;
			}
		}

		// Make sure they're unique and clean.
		$clean  = Helpers\sanitize_text_recursive( $query );

		// Get our initial user object.
		$user   = get_userdata( absint( $clean['customer_id'] ) );

		// Get the customer data, the user object.
		$relationship['customer']   = (array) $user->data;

		// Get the product data, the WP_Post object.
		$relationship['product']    = (array) get_post( absint( $clean['product_id'] ) );

		// And add the signup date.
		$relationship['signup']     = esc_attr( $clean['created'] );

		// Set our transient with our data.
		set_transient( $ky, $relationship, HOUR_IN_SECONDS );
	}

	// Return the relationship data.
	return $relationship;
}
