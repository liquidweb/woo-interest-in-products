<?php
/**
 * Our helper functions to use across the plugin.
 *
 * @package WooInterestInProducts
 */

// Declare our namespace.
namespace LiquidWeb\WooInterestInProducts\Helpers;

// Set our aliases.
use LiquidWeb\WooInterestInProducts as Core;
use LiquidWeb\WooInterestInProducts\Database as Database;

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
 * Check if we are on the account privacy data page.
 *
 * @param  boolean $in_query  Whether to check inside the actual query.
 *
 * @return boolean
 */
function maybe_account_endpoint_page( $in_query = false ) {

	// Bail if we aren't on the right general place.
	if ( is_admin() || ! is_account_page() ) {
		return false;
	}

	// Bail if we aren't on the right general place.
	if ( $in_query && ! in_the_loop() || $in_query && ! is_main_query() ) {
		return false;
	}

	// Call the global query object.
	global $wp_query;

	// Return if we are on our specific var or not.
	return isset( $wp_query->query_vars[ Core\FRONT_VAR ] ) ? true : false;
}

/**
 * Get our "My Account" page to use in the plugin.
 *
 * @param  array $args  Any query args to add to the base URL.
 *
 * @return string
 */
function get_account_tab_link( $args = array() ) {

	// Set my base link.
	$page   = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );

	// Add our link.
	$link   = rtrim( $page, '/' ) . '/' . Core\FRONT_VAR;

	// Return the link with or without args.
	return ! empty( $args ) ? add_query_arg( $args, $link ) : $link;
}

/**
 * Handle our redirect within the admin settings page.
 *
 * @param  array $args  The query args to include in the redirect.
 *
 * @return void
 */
function account_page_redirect( $args = array() ) {

	// Don't redirect if we didn't pass any args.
	if ( is_admin() || empty( $args ) ) {
		return;
	}

	// Do the redirect.
	wp_redirect( get_account_tab_link( $args ) );
	exit;
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

/**
 * Adjust the "My Account" menu to make sure login is at the bottom.
 *
 * @param  array $items  Our current array of items.
 *
 * @return array $items  The modified array.
 */
function adjust_account_tab_order( $items = array() ) {

	// If we don't have the logout link, just return what we have.
	if ( ! isset( $items['customer-logout'] ) ) {
		return $items;
	}

	// Set our logout link.
	$logout = $items['customer-logout'];

	// Remove the logout.
	unset( $items['customer-logout'] );

	// Now add it back in.
	$items['customer-logout'] = $logout;

	// And return the set.
	return $items;
}

/**
 * Return our base link, with function fallbacks.
 *
 * @return string
 */
function get_admin_menu_link() {

	// Bail if we aren't on the admin side.
	if ( ! is_admin() ) {
		return false;
	}

	// Set my slug.
	$slug   = trim( Core\MENU_SLUG );

	// Build out the link if we don't have our function.
	if ( ! function_exists( 'menu_page_url' ) ) {

		// Set my args.
		$args   = array( 'post_type' => 'product', 'page' => $slug );

		// Return the link with our args.
		return add_query_arg( $args, admin_url( 'edit.php' ) );
	}

	// Return using the function.
	return menu_page_url( $slug, false );
}

/**
 * Handle our redirect within the admin settings page.
 *
 * @param  array $args  The query args to include in the redirect.
 *
 * @return void
 */
function admin_page_redirect( $args = array(), $response = true ) {

	// Don't redirect if we didn't pass any args.
	if ( empty( $args ) ) {
		return;
	}

	// Handle the setup.
	$redirect_args  = wp_parse_args( $args, array( 'post_type' => 'product', 'page' => trim( Core\MENU_SLUG ) ) );

	// Add the default args we need in the return.
	if ( $response ) {
		$redirect_args  = wp_parse_args( array( 'wc-product-interest-response' => 1 ), $redirect_args );
	}

	// Now set my redirect link.
	$redirect_link  = add_query_arg( $redirect_args, admin_url( 'edit.php' ) );

	// Do the redirect.
	wp_safe_redirect( $redirect_link );
	exit;
}

/**
 * Check if we are on the admin settings tab.
 *
 * @param  string $hook  Optional hook sent from some actions.
 *
 * @return boolean
 */
function maybe_admin_settings_page( $hook = '' ) {

	// Can't be the admin page if we aren't admin, or don't have a hook.
	if ( ! is_admin() || empty( $hook ) ) {
		return false;
	}

	// Check the hook if we passed one.
	return 'product_page_product-interest-list' === sanitize_text_field( $hook ) ? true : false;
}

/**
 * Set up a recursive callback for multi-dimensional text arrays.
 *
 * @param  array   $input   The data array.
 * @param  boolean $filter  Whether to filter the empty values out.
 *
 * @return array
 */
function sanitize_text_recursive( $input, $filter = false ) {

	// Set our base output.
	$output = array();

	// Loop the initial data input set.
	// If our data is an array, kick it again.
	foreach ( $input as $key => $data ) {

		// Handle the setup.
		$setup  = is_array( $data ) ? array_map( 'sanitize_text_field', $data ) : sanitize_text_field( $data );

		// Skip if are empty and said no filter.
		if ( empty( $setup ) && ! empty( $filter ) ) {
			continue;
		}

		// Add the setup to the data array.
		$output[ $key ] = $setup;
	}

	// Return the entire set.
	return $output;
}

/**
 * Run our individual strings through some clean up.
 *
 * @param  string $string  The data we wanna clean up.
 *
 * @return string
 */
function clean_export( $string ) {

	// Original PHP code by Chirp Internet: www.chirp.com.au
	// Please acknowledge use of this code by including this header.

	// Handle my different string checks.
	switch ( $string ) {

		case 't':
			$string = 'TRUE';
			break;

		case 'f':
			$string = 'FALSE';
			break;

		case preg_match( "/^0/", $string ):
		case preg_match( "/^\+?\d{8,}$/", $string ):
		case preg_match( "/^\d{4}.\d{1,2}.\d{1,2}/", $string ):
			$string = "'$string";
			break;

		case strstr( $string, '"' ):
			$string = '"' . str_replace( '"', '""', $string ) . '"';
			break;

		default:
			$string = mb_convert_encoding( $string, 'UTF-16LE', 'UTF-8' );

		// End all case breaks.
	}
}

/**
 * Take a date in string format and handle the nicer display.
 *
 * @param  string $date    The date string.
 * @param  string $key     Optional single key to return.
 * @param  string $format  What date formatting we want.
 *
 * @return mixed
 */
function build_date_display( $date = '', $key = '', $format = '' ) {

	// Bail without a date.
	if ( empty( $date ) ) {
		return false;
	}

	// Grab the desired date formatting.
	$formatting = ! empty( $format ) ? esc_attr( $format ) : get_option( 'date_format', 'Y-m-d' );

	// Set the date to a stamp.
	$timestamp  = strtotime( $date );

	// Now set the formatting.
	$formatted  = date( $formatting, $timestamp );

	// Get my relative date.
	$relative   = sprintf( _x( '%s ago', '%s = human-readable time difference', 'woo-interest-in-products' ), human_time_diff( $timestamp, current_time( 'timestamp', 1 ) ) );

	// Now set an array of each value.
	$dataset    = array(
		'timestamp' => $timestamp,
		'formatted' => $formatted,
		'relative'  => $relative,
	);

	// Return the entire array if didn't specify a key.
	if ( ! $key ) {
		return $dataset;
	}

	// Now return the single requested key.
	return isset( $dataset[ $key ] ) ? $dataset[ $key ] : false;
}

/**
 * Remove individual relationships by ID.
 *
 * @param  array  $ids  The relationship IDs.
 *
 * @return mixed
 */
function remove_single_relationships( $ids = array() ) {

	// If we don't have ID, bail.
	if ( ! $ids ) {
		return false;
	}

	// Now loop my IDs and delete one by one.
	foreach ( (array) $ids as $id ) {

		// Run the delete process.
		$delete = Database\delete_by_relationship( $id );
	}

	// A basic thing for now.
	return true;
}
