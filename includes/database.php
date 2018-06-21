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
	$lookup = $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $table ) );

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
 * @param  integer $product_id  The product ID being purchased.
 * @param  integer $user_id     The user ID doing the purchasing.
 *
 * @return integer
 */
function insert( $product_id = 0, $user_id = 0 ) {

	// Make sure we have a product ID.
	if ( empty( $product_id ) || 'product' !== get_post_type( $product_id ) ) {
		return new WP_Error( 'invalid_product_id', __( 'The required product ID is missing or invalid.', 'woo-subscribe-to-products' ) );
	}

	// Make sure we have a user ID.
	if ( empty( $user_id ) ) {
		return new WP_Error( 'missing_user_id', __( 'The required user ID is missing.', 'woo-subscribe-to-products' ) );
	}

	// Call the global database.
	global $wpdb;

	// Set my insert data.
	$insert = array( 'product_id' => $product_id, 'user_id' => $user_id, 'created' => current_time( 'mysql' ) );

	// Filter our inserted data.
	$insert = apply_filters( Core\HOOK_PREFIX . 'insert_data', $insert );

	// Run our action after it has been inserted.
	do_action( Core\HOOK_PREFIX . 'before_insert', $insert );

	// Set our format clauses
	$format = array( '%d', '%d', '%s' );

	// Run my insert function.
	$wpdb->insert( $wpdb->wc_product_subscriptions, $insert, $format );

	// Run our action after it has been inserted.
	do_action( Core\HOOK_PREFIX . 'after_insert', $wpdb->insert_id, $insert );

	// Return the new relationship ID.
	return $wpdb->insert_id;
}
