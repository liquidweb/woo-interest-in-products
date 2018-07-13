<?php
/**
 * Our layout functions to use across the plugin.
 *
 * @package WooInterestInProducts
 */

// Declare our namespace.
namespace LiquidWeb\WooInterestInProducts\Layout;

// Set our aliases.
use LiquidWeb\WooInterestInProducts as Core;

/**
 * Build out and return the checkboxes for the checkout.
 *
 * @param  array   $products  The array of product IDs.
 * @param  boolean $echo      Whether to echo it out or return.
 *
 * @return HTML
 */
function get_optin_checkout_field( $products = array(), $echo = false ) {

	// Bail without any data.
	if ( empty( $products ) || ! is_array( $products ) ) {
		return;
	}

	// Set my label.
	$label  = apply_filters( Core\HOOK_PREFIX . 'checkout_label', __( 'Keep me informed about my products.', 'woo-interest-in-products' ), $products );

	// Set a string for my product IDs.
	$ids    = implode( ',', $products );

	// Set an empty.
	$build  = '';

	// Wrap the field in a paragraph.
	$build .= '<p class="form-row woo-interest-in-products-field">';

		// Output some label.
		$build .= '<label class="woocommerce-form__label woocommerce-form-woo-interest-in-products__label woocommerce-form__label-for-checkbox woo-interest-in-products-checkbox-label checkbox">';

			// Handle our field output.
			$build .= '<input class="woocommerce-form__input woocommerce-form-woo-interest-in-products__input-checkbox woocommerce-form__input-checkbox input-checkbox" name="woo-product-interest" id="" type="checkbox" value="' . esc_attr( $ids ) . '">';

			// Add the label text if present.
			$build .= '<span>' . esc_html( $label ) . '</span>';

		// And close the tag.
		$build .= '</label>';

		// Add a nonce field because nonce fields.
		$build .= wp_nonce_field( 'product_interest_nonce_action', 'product_interest_nonce_name', false, false );

	// Close the single paragraph.
	$build .= '</p>';

	// And echo it out if requested.
	if ( $echo ) {
		echo $build; // WPCS: XSS ok.
	}

	// Just return it.
	return $build;
}

/**
 * Build out and return the list of signed up customers.
 *
 * @param  array   $customers  The array of customer IDs.
 * @param  boolean $echo       Whether to echo it out or return.
 *
 * @return HTML
 */
function get_subscribed_customers_list( $customers = array(), $echo = false ) {

	// Bail without any customers.
	if ( empty( $customers ) || ! is_array( $customers ) ) {
		return;
	}

	// Set an empty.
	$build  = '';

	// Begin the list output.
	$build .= '<ol>';

	// Now we loop the IDs and show them individually.
	foreach ( $customers as $customer_id ) {

		// Pull out the user object and profile link.
		$user   = get_userdata( $customer_id );
		$link   = get_edit_user_link( $customer_id );

		// And the output.
		$build .= '<li>';
			$build .= '<a href="' . esc_url( $link ) . '">' . esc_html( $user->display_name ) . '</a>';
			$build .= ' <strong>(' . esc_html__( 'ID:', 'woo-interest-in-products' ) . ' ' . absint( $customer_id ) . ')</strong>';
		$build .= '</li>';
	}

	// Close the list.
	$build .= '</ol>';

	// And echo it out if requested.
	if ( $echo ) {
		echo $build; // WPCS: XSS ok.
	}

	// Just return it.
	return $build;
}
