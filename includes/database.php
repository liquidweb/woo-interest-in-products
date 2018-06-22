<?php
/**
 * All functions related to the custom tables.
 *
 * @package WooSubscribeToProducts
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProducts\Database;

// Set our aliases.
use LiquidWeb\WooSubscribeToProducts as Core;
use LiquidWeb\WooSubscribeToProducts\Helpers as Helpers;

/**
 * Start our engines.
 */
add_action( 'plugins_loaded', __NAMESPACE__ . '\register_table', 11 );

/**
 * Registers the table with $wpdb so the metadata api can find it.
 *
 * @return void
 */
function register_table() {

	// Load the global DB.
	global $wpdb;

	// Set the messages.
	$wpdb->wc_product_subscriptions = $wpdb->prefix . Core\TABLE_NAME;
}

/**
 * Confirm that the table itself actually exists.
 *
 * @return boolean
 */
function maybe_table_exists() {

	// Call the global class.
	global $wpdb;

	// Set my table name.
	$table  = $wpdb->prefix . Core\TABLE_NAME;

	// Run the lookup.
	$lookup = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) );

	// We have the table, no need to install it.
	$wpdb->get_var( $lookup ) === $table ? true : false;
}

/**
 * Confirm that the table itself actually exists.
 *
 * @return boolean
 */
function maybe_install_table() {

	// Run the check.
	$check  = maybe_table_exists();

	// If the table exists, just bail.
	if ( $check ) {
		rturn;
	}

	// Run the install setup.
	install_table();
}

/**
 * Compare the stored version of the database schema.
 *
 * @return boolean
 */
function maybe_update_table() {

	// We're already updated and current, so nothing here.
	if ( (int) get_option( Core\SCHEMA_KEY ) === (int) Core\DB_VERS ) {
		return false;
	}

	// Run the install setup.
	install_table();
}

/**
 * Create our custom table to store the subscription relationships.
 *
 * @return void
 */
function install_table() {

	// Pull in the upgrade function.
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	// Load the WPDB global.
	global $wpdb;

	// Pull our character set and collating.
	$char_coll  = $wpdb->get_charset_collate();

	// Set our various table names.
	$table_name = $wpdb->prefix . Core\TABLE_NAME;
	$post_table = $wpdb->posts;
	$user_table = $wpdb->users;

	// Write the SQL syntax.
	$setup  = "
		CREATE TABLE {$table_name} (
			relationship_id bigint(20) NOT NULL AUTO_INCREMENT,
			product_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			created datetime NOT NULL,
		PRIMARY KEY  (relationship_id),
		KEY product_id (product_id),
		KEY user_id (user_id)
	) $char_coll;";

	// Create the actual table.
	dbDelta( $setup );

	// And run the update.
	update_option( Core\SCHEMA_KEY, Core\DB_VERS );

	// And return true because it exists.
	return true;
}

/**
 * Create a new record of a subscription.
 *
 * @param  integer $user_id   The user ID doing the purchasing.
 * @param  array   $products  The array of product IDs being passed.
 *
 * @return integer
 */
function insert( $user_id = 0, $products = array() ) {

	// Make sure we have a user ID.
	if ( empty( $user_id ) ) {
		return new WP_Error( 'missing_user_id', __( 'The required user ID is missing.', 'woo-subscribe-to-products' ) );
	}

	// Make sure we have products.
	if ( empty( $products ) || ! is_array( $products ) ) {
		return new WP_Error( 'missing_invalid_products', __( 'The required product IDs are missing or invalid.', 'woo-subscribe-to-products' ) );
	}

	// Run the action before doing anything.
	do_action( Core\HOOK_PREFIX . 'before_all_inserts', $user_id, $products );

	// Call the global database.
	global $wpdb;

	// Set our created time.
	$create = current_time( 'mysql' );

	// Loop my products and confirm each one.
	foreach ( $products as $product_id ) {

		// Make sure we have a product ID.
		if ( empty( $product_id ) || 'product' !== get_post_type( $product_id ) ) {
			return new WP_Error( 'invalid_product_id', __( 'The required product ID is missing or invalid.', 'woo-subscribe-to-products' ) );
		}

		// Set my insert data.
		$insert = array( 'product_id' => $product_id, 'user_id' => $user_id, 'created' => $create );

		// Filter our inserted data.
		$insert = apply_filters( Core\HOOK_PREFIX . 'insert_data', $insert, $user_id, $product_id );

		// Run our action after it has been inserted.
		do_action( Core\HOOK_PREFIX . 'before_insert', $insert, $user_id, $product_id );

		// Set our format clauses
		$format = apply_filters( Core\HOOK_PREFIX . 'insert_data_format', array( '%d', '%d', '%s' ) );

		// Run my insert function.
		$wpdb->insert( $wpdb->wc_product_subscriptions, $insert, $format );

		// Check for the ID and throw an error if we don't have it.
		if ( ! $wpdb->insert_id ) {
			return new WP_Error( 'db_insert_error', __( 'There was an error adding this to the database.', 'woo-subscribe-to-products' ) );
		}

		// Run our action after it has been inserted.
		do_action( Core\HOOK_PREFIX . 'after_insert', $wpdb->insert_id, $insert, $user_id, $product_id );
	}

	// Run the action after doing everything.
	do_action( Core\HOOK_PREFIX . 'after_all_inserts', $user_id, $products );

	// Return true.
	return true;
}
