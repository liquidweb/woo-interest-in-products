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

/**
 * Start our engines.
 */
add_action( 'woocommerce_product_options_advanced', __NAMESPACE__ . '\display_product_subscribe' );
add_action( 'woocommerce_process_product_meta', __NAMESPACE__ . '\save_product_subscribe' );

/**
 * Display the checkbox for including an opt-in.
 *
 * @return HTML
 */
function display_product_subscribe() {

	// Call our global.
	global $post;

	// Check the meta.
	$meta   = get_post_meta( $post->ID, Core\PROD_META_KEY, true );

	// Output our checkbox all Woo style.
	woocommerce_wp_checkbox(
		array(
			'id'            => 'include_product_subscribe',
			'value'         => ! empty( $meta ) ? 'yes' : 'no',
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

	// @@todo add the product ID to some other option?
}
