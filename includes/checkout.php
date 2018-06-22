<?php
/**
 * Our functions related to the checkout.
 *
 * @package WooSubscribeToProducts
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProducts\Checkout;

// Set our aliases.
use LiquidWeb\WooSubscribeToProducts as Core;
use LiquidWeb\WooSubscribeToProducts\Helpers as Helpers;
use LiquidWeb\WooSubscribeToProducts\Layout as Layout;
use LiquidWeb\WooSubscribeToProducts\Queries as Queries;
use LiquidWeb\WooSubscribeToProducts\Database as Database;

/**
 * Start our engines.
 */
add_action( 'woocommerce_review_order_before_submit', __NAMESPACE__ . '\display_product_subscribe_fields' );
add_filter( 'woocommerce_checkout_posted_data', __NAMESPACE__ . '\merge_product_subscribe_data' );
add_action( 'woocommerce_after_checkout_validation', __NAMESPACE__ . '\validate_product_subscribe_data', 10, 2 );
add_action( 'woocommerce_checkout_update_user_meta', __NAMESPACE__ . '\update_customer_product_subscriptions', 10, 2 );

/**
 * Add our new opt-in boxes to the checkout.
 *
 * @return HTML
 */
function display_product_subscribe_fields() {

	// Don't run any of this on a guest user.
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Bail without some cart action.
	if ( ! WC()->cart->get_cart_contents() ) {
		return;
	}

	// Get my array of enabled products.
	$enable = Queries\get_enabled_products();

	// No enabled products exist, so bail.
	if ( empty( $enable ) ) {
		return;
	}

	// Filter my cart products.
	$filter = Helpers\filter_product_cart( WC()->cart->get_cart_contents(), $enable );

	// Bail without having any items come back.
	if ( ! $filter ) {
		return;
	}

	// And spit out the fields.
	Layout\get_optin_checkout_field( $filter, true );
}

/**
 * Merge in our posted field data.
 *
 * @param  array  $data  The post data that comes by default.
 *
 * @return array  $data  The possibly modified posted data.
 */
function merge_product_subscribe_data( $data ) {

	// Don't run any of this on a guest user.
	if ( ! is_user_logged_in() ) {
		return $data;
	}

	// Bail if we have no posted data.
	if ( empty( $_POST['woo-product-subscribe'] ) ) { // WPCS: CSRF ok.
		return $data;
	}

	// Check to see if our nonce was provided.
	if ( empty( $_POST['customer_prodsub_nonce_name'] ) || ! wp_verify_nonce( $_POST['customer_prodsub_nonce_name'], 'customer_prodsub_nonce_action' ) ) {
		return $data;
	}

	// Explode the string of IDs into an array..
	$array  = explode( ',', sanitize_text_field( $_POST['woo-product-subscribe'] ) );

	// Clean each entry.
	$items  = array_map( 'absint', $array );

	// Merge our opt-in items to the overall data array and return it.
	return array_merge( $data, array( 'subscribed-products' => $items ) );
}

/**
 * Validate the opt-in fields.
 *
 * @param  array  $data    The post data that comes by default.
 * @param  object $errors  The existing error object.
 *
 * @return mixed
 */
function validate_product_subscribe_data( $data, $errors ) {

	// Don't run any of this on a guest user.
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Bail if we have no subscriber data.
	if ( empty( $data['subscribed-products'] ) ) {
		return;
	}

	// Now loop my products and make sure they are actually products.
	foreach ( $data['subscribed-products'] as $product_id ) {

		// First make sure it is a product.
		if ( 'product' !== get_post_type( $product_id ) ) {

			// Set our error message.
			$error_text = sprintf( __( 'ID %d is not a valid product', 'woo-subscribe-to-products' ), absint( $product_id ) );

			// And add my error.
			$errors->add( 'invalid_product_id', $error_text );
		}

		// Now check to make sure it's enabled..
		$enable = Helpers\maybe_product_enabled( $product_id );

		// Now make sure it is an enabled product.
		if ( ! $enable ) {

			// Set our error message.
			$error_text = sprintf( __( 'Signups have not been enabled for product ID %d', 'woo-subscribe-to-products' ), absint( $product_id ) );

			// And add my error.
			$errors->add( 'product_not_enabled', $error_text );
		}
	}

	// And just be done.
	return;
}

/**
 * Update the opt-in field choices for the customer.
 *
 * @param  integer $customer_id  The WooCommerce customer ID.
 * @param  array   $data         The post data from the order.
 *
 * @return void
 */
function update_customer_product_subscriptions( $customer_id, $data ) {

	// Don't run any of this on a guest user.
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Bail without data or customer info.
	if ( empty( $customer_id ) || empty( $data ) || ! is_array( $data ) ) {
		return;
	}

	// If we don't have any products, just bail.
	if ( empty( $data['subscribed-products'] ) ) {
		return;
	}

	// Set my products.
	$items  = array_map( 'absint', $data['subscribed-products'] );

	// Run the inserts.
	$update = Database\insert( $customer_id, (array) $items );

	// @@todo  handle error / empty return?
}
