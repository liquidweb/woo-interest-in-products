<?php
/**
 * Our activation call
 *
 * @package WooSubscribeToProduct
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProduct\Activate;

// Set our aliases.
use LiquidWeb\WooSubscribeToProduct as Core;

/**
 * Our inital setup function when activated.
 *
 * @return void
 */
function activate() {

	// Include our action so that we may add to this later.
	do_action( 'woo_subscribe_product_activate_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( Core\FILE, __NAMESPACE__ . '\activate' );
