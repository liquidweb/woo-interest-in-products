<?php
/**
 * The functionality tied to the settings tab.
 *
 * @package WooSubscribeToProducts
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProducts\SettingsTab;

// Set our aliases.
use LiquidWeb\WooSubscribeToProducts as Core;
use LiquidWeb\WooSubscribeToProducts\Helpers as Helpers;
use LiquidWeb\WooSubscribeToProducts\Queries as Queries;

/**
 * Start our engines.
 */
add_action( 'admin_menu', __NAMESPACE__ . '\load_settings_menu', 99 );

/**
 * Load our menu item.
 *
 * @return void
 */
function load_settings_menu() {

	// Add our submenu page.
	add_submenu_page(
		'edit.php?post_type=product',
		__( 'Product Subscriptions', 'woo-subscribe-to-products' ),
		__( 'Subscriptions', 'woo-subscribe-to-products' ),
		'manage_options',
		Core\MENU_SLUG,
		__NAMESPACE__ . '\settings_page_view'
	);
}

/**
 * Our actual settings page for things.
 *
 * @return mixed
 */
function settings_page_view() {

	// Wrap the entire thing.
	echo '<div class="wrap wc-product-subscriptions-admin-wrap">';

		// Handle the title.
		echo '<h1 class="wc-product-subscriptions-admin-title">' . get_admin_page_title() . '</h1>';

		// Handle the table.
		echo subscription_list_table();

	// Close the entire thing.
	echo '</div>';
}

/**
 * Create and return the table of subscription data.
 *
 * @param  array  $requests  The existing requests.
 *
 * @return HTML
 */
function subscription_list_table() {

	// Pull our list of enabled products.
	$products   = Queries\get_enabled_products();

	// Bail if we don't have any.
	if ( empty( $products ) ) {

		// Echo out the message.
		echo '<p>' . esc_html__( 'There are no enabled product subscriptions.', 'woo-subscribe-to-products' ) . '</p>';

		// And be done.
		return;
	}

	// Fetch the action link.
	$action = Helpers\get_admin_menu_link();

	// Call our table class.
	$table  = new \SingleProductSubscriptions_Table();

	// And output the table.
	$table->prepare_items();

	// And handle the display
	echo '<form class="wc-product-subscriptions-admin-form" id="wc-product-subscriptions-products-admin-form" action="' . esc_url( $action ) . '" method="post">';

	// The actual table itself.
	$table->display();

	// And close it up.
	echo '</form>';
}
