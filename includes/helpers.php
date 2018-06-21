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
 * Check the products provided for enabled items.
 *
 * @param  array $cart      The total array of cart data.
 * @param  array $products  The enabled products.
 *
 * @return array
 */
function filter_product_cart( $cart = array(), $products = array() ) {

	// Make sure we have everything required.
	if ( empty( $cart ) || empty( $products ) ) {
		return false;
	}

	// Set an empty variable.
	$filter = array();

	// Loop our cart and look for products.
	foreach ( $cart as $key => $item ) {

		// Set my ID.
		$id = absint( $item['product_id'] );

		// Check the meta.
		$mt = get_post_meta( $id, Core\PROD_META_KEY, true );

		// If we have meta, add to the data array.
		if ( ! empty( $mt ) ) {
			$filter[ $id ]  = get_the_title( $id );
		}
	}

	// Return the array (or empty).
	return ! empty( $filter ) ? $filter : false;
}
