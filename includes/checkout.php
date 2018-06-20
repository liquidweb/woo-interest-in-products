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
add_action( 'woocommerce_checkout_update_customer', __NAMESPACE__ . '\update_product_subscribe_data', 10, 2 );

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

	// Set an empty.
	$build  = '';

	// Loop my fields and set up each one.
	foreach ( $setup as $id => $title ) {

		// Make a label and a slug.
		$slug   = sanitize_title_with_dashes( $title, '', 'save' );
		$label  = Layout\get_optin_checkout_label( $title, $id );

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

	// And echo it out.
	echo $build; // WPCS: XSS ok.
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
	// @@todo what needs to be validated?
}

/**
 * Update the opt-in field choices for the user.
 *
 * @param  object $customer  The WooCommerce customer object.
 * @param  array  $data      The post data from the order.
 *
 * @return void
 */
function update_user_product_subscriptions( $customer, $data ) {

	// Bail without data or customer info.
	if ( empty( $customer ) || ! is_object( $customer ) || empty( $data ) || ! is_array( $data ) ) {
		return;
	}

	// Check for the product IDs being passed.
	$products   = ! empty( $data['subscribed-products'] ) ? $data['subscribed-products'] : array();

	// Update the user meta item.
	Database\insert( 0, $customer, $products );

	// And just be done.
	return;
}
