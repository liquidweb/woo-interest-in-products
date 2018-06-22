<?php
/**
 * The individual post meta setup.
 *
 * @package WooSubscribeToProducts
 */

// Declare our namespace.
namespace LiquidWeb\WooSubscribeToProducts\PostMeta;

// Set our aliases.
use LiquidWeb\WooSubscribeToProducts as Core;
use LiquidWeb\WooSubscribeToProducts\Helpers as Helpers;
use LiquidWeb\WooSubscribeToProducts\Layout as Layout;
use LiquidWeb\WooSubscribeToProducts\Queries as Queries;

/**
 * Start our engines.
 */
add_action( 'add_meta_boxes_product', __NAMESPACE__ . '\load_subscribed_customers_metabox', 11 );
add_action( 'woocommerce_product_options_advanced', __NAMESPACE__ . '\display_product_subscribe_checkbox' );
add_action( 'woocommerce_process_product_meta', __NAMESPACE__ . '\save_product_subscribe' );

/**
 * Display a metabox with all the subscribed users.
 *
 * @param  object $post  The post object.
 *
 * @return void
 */
function load_subscribed_customers_metabox( $post ) {

	// Bail if we don't have an enabled product.
	if ( ! Helpers\maybe_product_enabled( $post->ID ) ) {
		return;
	}

	// Load the box.
	add_meta_box( 'list-product-subs', __( 'Subscribed Customers', 'woo-subscribe-to-products' ), __NAMESPACE__ . '\display_subscribed_customers', 'product', 'side', 'default' );
}

/**
 * Load the metabox for all the subscribed users.
 *
 * @param  object $post  The post object we are currently working with.
 *
 * @return HTML
 */
function display_subscribed_customers( $post ) {

	// Grab the customers.
	$customers  = Queries\get_customers_for_product( $post->ID );

	// If no customers exist, display a message and bail.
	if ( ! $customers ) {

		// Output the actual message.
		echo '<p class="howto">' . esc_html__( 'No customers have subscribed to this product.', 'woo-subscribe-to-products' ) . '</p>';

		// And bail.
		return;
	}

	// We have customers, so lets set up a list.
	Layout\get_subscribed_customers_list( $customers, true );
}

/**
 * Display the checkbox for including an opt-in.
 *
 * @return HTML
 */
function display_product_subscribe_checkbox() {

	// Call our global.
	global $post;

	// Output our checkbox all Woo style.
	woocommerce_wp_checkbox(
		array(
			'id'            => 'include_product_subscribe',
			'value'         => Helpers\maybe_product_enabled( $post->ID, true ),
			'wrapper_class' => 'show_if_simple show_if_variable show_if_external hide_if_grouped',
			'label'         => __( 'Include product signup', 'woo-subscribe-to-products' ),
			'description'   => __( 'Displays a checkbox for this particular product at checkout.', 'woo-subscribe-to-products' ),
			'cbvalue'       => 'yes',
		)
	);

	// Include the nonce field.
	wp_nonce_field( 'woo_prodsubs_nonce_action', 'woo_prodsubs_nonce_name', false, true );
}

/**
 * Save whether or not the product should display an opt-in.
 *
 * @param  integer $post_id  The individual post ID.
 *
 * @return void
 */
function save_product_subscribe( $post_id ) {

	// Bail if it isn't an actual product.
	if ( 'product' !== get_post_type( $post_id ) ) {
		return;
	}

	// Make sure the current user has the ability to save.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Check to see if our nonce was provided.
	if ( empty( $_POST['woo_prodsubs_nonce_name'] ) || ! wp_verify_nonce( $_POST['woo_prodsubs_nonce_name'], 'woo_prodsubs_nonce_action' ) ) {
		return;
	}

	// Get our product.
	$prod   = wc_get_product( $post_id );

	// Handle the meta based on what was passed.
	if ( ! empty( $_POST['include_product_subscribe'] ) ) {
		$prod->update_meta_data( Core\PROD_META_KEY, true );
	} else {
		$prod->delete_meta_data( Core\PROD_META_KEY );
	}

	// And save the product.
	$prod->save();

	// Delete the transient storing the enabled products.
	delete_transient( 'woo_product_subscription_ids' );

	// @@todo add the product ID to some other option?
}
