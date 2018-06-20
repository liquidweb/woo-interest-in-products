<?php
/**
 * Our layout functions to use across the plugin.
 *
 * @package WooSubscribeToProducts
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProducts\Layout;

// Set our aliases.
use LiquidWeb\WooSubscribeToProducts as Core;

/**
 * Create the label to show on the checkout.
 *
 * @param  string  $product_title  The base title of the product.
 * @param  integer $product_id     The product ID.
 *
 * @return string
 */
function get_optin_checkout_label( $product_title = '', $product_id = 0 ) {

	// Set the basic label.
	$label  = sprintf( __( 'Keep me informed about %s', 'woo-subscribe-to-products' ), esc_attr( $product_title ) );

	// Return the label, filtered.
	return apply_filters( 'woo_subscribe_products_checkout_label', $label, $product_id, $product_title );
}

