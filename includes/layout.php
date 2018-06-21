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
	return apply_filters( Core\HOOK_PREFIX . 'checkout_label', $label, $product_id, $product_title );
}

/**
 * Build out and return the checkboxes for the checkout.
 *
 * @param  array   $data  The array of opt-in data.
 * @param  boolean $echo  Whether to echo it out or return.
 *
 * @return HTML
 */
function get_optin_checkout_fields( $data = array(), $echo = false ) {

	// Bail without any data.
	if ( empty( $data ) || ! is_array( $data ) ) {
		return;
	}

	// Set an empty.
	$build  = '';

	// Loop my fields and set up each one.
	foreach ( $data as $id => $title ) {

		// Make a label and a slug.
		$slug   = sanitize_title_with_dashes( $title, '', 'save' );
		$label  = get_optin_checkout_label( $title, $id );

		// Wrap each one in a paragraph.
		$build .= '<p class="form-row lw-woo-gdpr-' . esc_attr( $slug ) . '-field">';

			// Output some label.
			$build .= '<label class="woocommerce-form__label woocommerce-form-' . esc_attr( $slug ) . '__label woocommerce-form__label-for-checkbox woo-subscribe-products-checkbox-label checkbox">';

				// Handle our field output.
				$build .= '<input class="woocommerce-form__input woocommerce-form-' . esc_attr( $slug ) . '__input-checkbox woocommerce-form__input-checkbox input-checkbox" name="woo-product-subscribe[]" id="" type="checkbox" value="' . absint( $id ) . '">';

				// Add the label text if present.
				$build .= '<span>' . esc_html( $label ) . '</span>';

		// Close the single paragraph.
		$build .= '</p>';
	}

	// And echo it out if requested.
	if ( $echo ) {
		echo $build; // WPCS: XSS ok.
	}

	// Just return it.
	return $build;
}

/**
 * Build out and return the list of subscribed customers.
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
			$build .= ' <strong>(' . esc_html__( 'ID:', 'woo-subscribe-to-products' ) . ' ' . absint( $customer_id ) . ')</strong>';
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
