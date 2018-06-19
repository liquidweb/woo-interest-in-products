<?php
/**
 * Our uninstall call
 *
 * @package WooSubscribeToProduct
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProduct\Uninstall;

// Set our aliases.
use LiquidWeb\WooSubscribeToProduct as Core;

/**
 * Delete various options when uninstalling the plugin.
 *
 * @return void
 */
function uninstall() {

	// Include our action so that we may add to this later.
	do_action( 'woo_subscribe_product_uninstall_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_uninstall_hook( Core\FILE, __NAMESPACE__ . '\uninstall' );
