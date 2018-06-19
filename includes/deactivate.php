<?php
/**
 * Our deactivation call
 *
 * @package WooSubscribeToProduct
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProduct\Deactivate;

// Set our aliases.
use LiquidWeb\WooSubscribeToProduct as Core;

/**
 * Delete various options when deactivating the plugin.
 *
 * @return void
 */
function deactivate() {

	// Include our action so that we may add to this later.
	do_action( 'woo_subscribe_product_deactivate_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( Core\FILE, __NAMESPACE__ . '\deactivate' );
