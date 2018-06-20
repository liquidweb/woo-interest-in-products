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
