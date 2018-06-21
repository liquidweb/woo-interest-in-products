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
use LiquidWeb\WooSubscribeToProducts\Database as Database;

/**
 * Start our engines.
 */
add_action( 'woocommerce_review_order_before_submit', __NAMESPACE__ . '\display_product_subscribe_fields' );
add_filter( 'woocommerce_checkout_posted_data', __NAMESPACE__ . '\merge_product_subscribe_data' );
add_action( 'woocommerce_after_checkout_validation', __NAMESPACE__ . '\validate_product_subscribe_data', 10, 2 );
add_action( 'woocommerce_checkout_update_user_meta', __NAMESPACE__ . '\update_user_product_subscriptions', 10, 2 );

/**
 * Add our new opt-in boxes to the checkout.
 *
 * @return HTML
 */
function display_product_subscribe_fields() {

	// Bail without some cart action.
	if ( ! WC()->cart->get_cart_contents() ) {
		return;
	}

	// Set an empty variable.
	$setup  = array();

	// Loop our cart and look for products.
	foreach ( WC()->cart->get_cart_contents() as $key => $item ) {

		// Set my ID.
		$prodid = absint( $item['product_id'] );

		// Check the meta.
		$meta   = get_post_meta( $prodid, Core\PROD_META_KEY, true );

		// Bail without having any meta.
		if ( empty( $meta ) ) {
			continue;
		}

		// Now add the ID and title to an array.
		$setup[ $prodid ] = get_the_title( $prodid );
	}

	// Bail without having any items.
	if ( empty( $setup ) ) {
		return;
	}

	// And spit out the fields.
	Layout\get_optin_checkout_fields( $setup, true );
}

/**
 * Merge in our posted field data.
 *
 * @param  array  $data  The post data that comes by default.
 *
 * @return array  $data  The possibly modified posted data.
 */
function merge_product_subscribe_data( $data ) {

	// Bail if we have no posted data.
	if ( empty( $_POST['woo-product-subscribe'] ) ) { // WPCS: CSRF ok.
		return $data;
	}

	// Clean each entry.
	$items  = array_map( 'absint', $_POST['woo-product-subscribe'] );

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

		// Now check the meta.
		$meta   = get_post_meta( $product_id, Core\PROD_META_KEY, true );

		// Now make sure it is an enabled product.
		if ( ! $meta ) {

			// Set our error message.
			$error_text = sprintf( __( 'Signups have not been enabled for product ID %d', 'woo-subscribe-to-products' ), absint( $product_id ) );

			// And add my error.
			$errors->add( 'disabled_product_id', $error_text );
		}
	}

	// And just be done.
	return;
}

/**
 * Update the opt-in field choices for the user.
 *
 * @param  integer $customer_id  The WooCommerce customer ID.
 * @param  array   $data         The post data from the order.
 *
 * @return void
 */
function update_user_product_subscriptions( $customer_id, $data ) {

	// Bail without data or customer info.
	if ( empty( $customer_id ) || empty( $data ) || ! is_array( $data ) ) {
		return;
	}

	// If we don't have any products, just bail.
	if ( empty( $data['subscribed-products'] ) ) {
		return;
	}

	// Set my products.
	$products   = array_map( 'absint', $data['subscribed-products'] );

	// Loop the product IDs and run thge insert.
	foreach ( $products as $product_id ) {
		Database\insert( $customer_id, $product_id );
	}

}
