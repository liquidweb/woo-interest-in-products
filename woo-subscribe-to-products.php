<?php
/**
 * Plugin Name: WooCommerce Subscribe To Products
 * Plugin URI:  https://github.com/liquidweb/woo-subscribe-to-products
 * Description: Allow customers to opt-in to notices about individual products.
 * Version:     0.0.1
 * Author:      Liquid Web
 * Author URI:  https://www.liquidweb.com
 * Text Domain: woo-subscribe-to-products
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package WooSubscribeToProducts
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProducts;

// Call our CLI namespace.
use WP_CLI;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Define our plugin version.
define( __NAMESPACE__ . '\VERS', '0.0.1' );

// Define our database version.
define( __NAMESPACE__ . '\DB_VERS', '1' );

// Plugin root file.
define( __NAMESPACE__ . '\FILE', __FILE__ );

// Define our file base.
define( __NAMESPACE__ . '\BASE', plugin_basename( __FILE__ ) );

// Plugin Folder URL.
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

// Set our assets directory constant.
define( __NAMESPACE__ . '\ASSETS_URL', URL . 'assets' );

// Set our tab base slug constant.
define( __NAMESPACE__ . '\MENU_SLUG', 'single-product-subs' );

// Set our custom table name.
define( __NAMESPACE__ . '\TABLE_NAME', 'wc_product_subscriptions' );

// Set the prefix for our actions and filters.
define( __NAMESPACE__ . '\HOOK_PREFIX', 'woo_subscribe_products_' );

// Set the option key used to store the schema.
define( __NAMESPACE__ . '\SCHEMA_KEY', HOOK_PREFIX . 'db_version' );

// Set the meta key name for products.
define( __NAMESPACE__ . '\PROD_META_KEY', '_product_subscribe_enabled' );

// Load the multi-use files.
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/database.php';

// Load the triggered file loads.
require_once __DIR__ . '/includes/activate.php';
require_once __DIR__ . '/includes/deactivate.php';
require_once __DIR__ . '/includes/uninstall.php';

// Load the individual files.
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/queries.php';
require_once __DIR__ . '/includes/post-meta.php';
require_once __DIR__ . '/includes/checkout.php';
require_once __DIR__ . '/includes/table-views.php';
require_once __DIR__ . '/includes/settings-tab.php';


// Check that we have the constant available.
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	// Load our commands file.
	require_once dirname( __FILE__ ) . '/includes/commands.php';

	// And add our command.
	WP_CLI::add_command( 'woo-subscribe-products', Commands::class );
}
