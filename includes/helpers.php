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
 * Check a product ID to see if it enabled.
 *
 * @param  integer $product_id  The ID of the product.
 * @param  boolean $strings     Optional return of yes/no strings.
 *
 * @return mixed
 */
function maybe_product_enabled( $product_id = 0, $strings = false ) {

	// Check the meta.
	$meta   = get_post_meta( $product_id, Core\PROD_META_KEY, true );

	// Return the string variant if requested.
	if ( $strings ) {
		return ! empty( $meta ) ? 'yes' : 'no';
	}

	// Return the boolean result.
	return ! empty( $meta ) ? true : false;
}

/**
 * Check the products provided for enabled items.
 *
 * @param  array $cart    The total array of cart data.
 * @param  array $enable  The enabled products.
 *
 * @return array
 */
function filter_product_cart( $cart = array(), $enable = array() ) {

	// Make sure we have everything required.
	if ( empty( $cart ) || empty( $enable ) ) {
		return false;
	}

	// Set an empty variable.
	$data   = array();

	// Loop our cart and look for products.
	foreach ( $cart as $key => $item ) {

		// Set my ID.
		$id = absint( $item['product_id'] );

		// If we have meta, add to the data array.
		if ( in_array( $id, $enable ) ) {
			$data[] = $id;
		}
	}

	// Return the array (or empty).
	return ! empty( $data ) ? $data : false;
}
