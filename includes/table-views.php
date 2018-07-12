<?php
/**
 * Our table setup for the handling the data pieces.
 *
 * @package WooSubscribeToProducts
 */

// Set our aliases.
use LiquidWeb\WooSubscribeToProducts as Core;
use LiquidWeb\WooSubscribeToProducts\Helpers as Helpers;
use LiquidWeb\WooSubscribeToProducts\Database as Database;
use LiquidWeb\WooSubscribeToProducts\Queries as Queries;
use LiquidWeb\WooSubscribeToProducts\DataExport as Export;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create a new table class that will extend the WP_List_Table.
 */
class SingleProductSubscriptions_Table extends WP_List_Table {

	/**
	 * SingleProductSubscriptions_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {

		// Set parent defaults.
		parent::__construct( array(
			'singular' => __( 'Single Product Subscriptions', 'liquidweb-woocommerce-gdpr' ),
			'plural'   => __( 'Single Product Subscriptions', 'liquidweb-woocommerce-gdpr' ),
			'ajax'     => false,
		) );
	}

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items() {

		// Roll out each part.
		$columns    = $this->get_columns();
		$hidden     = $this->get_hidden_columns();
		$sortable   = $this->get_sortable_columns();
		$dataset    = $this->table_data();

		// Check for the _POST value to filter.
		if ( ! empty( $_POST['wc-product-subs-filter-submit' ] ) ) {
			$dataset    = $this->maybe_filter_dataset( $dataset );
		}

		// Handle our sorting.
		usort( $dataset, array( $this, 'sort_data' ) );

		// Load up the pagination settings.
		$paginate   = 10;
		$item_count = count( $dataset );
		$current    = $this->get_pagenum();

		// Set my pagination args.
		$this->set_pagination_args( array(
			'total_items' => $item_count,
			'per_page'    => $paginate,
			'total_pages' => ceil( $item_count / $paginate ),
		));

		// Slice up our dataset.
		$dataset    = array_slice( $dataset, ( ( $current - 1 ) * $paginate ), $paginate );

		// Do the column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Make sure we have the single action running.
		$this->process_single_action();

		// Make sure we have the bulk action running.
		$this->process_bulk_action();

		// And the result.
		$this->items = $dataset;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @return Array
	 */
	public function get_columns() {

		// Build our array of column setups.
		$setup  = array(
			'cb'            => '<input type="checkbox" />',
			'visible_name'  => __( 'Customer Name', 'woo-subscribe-to-products' ),
			'product_name'  => __( 'Product Name', 'woo-subscribe-to-products' ),
			'signup_date'   => __( 'Signup Date', 'woo-subscribe-to-products' ),
			'action_list'   => __( 'Actions', 'woo-subscribe-to-products' ),
		);

		// Return filtered.
		return apply_filters( Core\HOOK_PREFIX . 'table_column_items', $setup );
	}

	/**
	 * Display all the things.
	 *
	 * @return HTML
	 */
	public function display() {

		// Add a nonce for the bulk action.
		wp_nonce_field( 'wc_product_subs_nonce_action', 'wc_product_subs_nonce_name' );

		// And the parent display (which is most of it).
		parent::display();
	}

	/**
	 * Add extra markup in the toolbars before or after the list.
	 *
	 * @param string $which  Which markup area after (bottom) or before (top) the list.
	 */
	protected function extra_tablenav( $which ) {

		// Bail if we aren't on the top.
		if ( 'top' !== $which ) {
			return;
		}

		// Begin the markup.
		echo '<div class="alignleft actions">';

			// The product dropdown.
			$this->product_filter_dropdown();

			// The customer dropdown.
			$this->customer_filter_dropdown();

			// And handle our button.
			echo '<button class="button action" name="wc-product-subs-filter-submit" type="submit" value="1">' . esc_html__( 'Filter', 'woo-subscribe-to-products' ) . '</button>';

		// Close the div.
		echo '</div>';
	}

	/**
	 * Build and display the dropdown for products.
	 *
	 * @param  boolean $echo  Whether to echo out the markup or return.
	 *
	 * @return HTML
	 */
	protected function product_filter_dropdown( $echo = true ) {

		// Get our enabled products.
		$enabled_products   = Queries\get_enabled_products();

		// Bail if we don't have any products to filter by.
		if ( ! $enabled_products ) {
			return;
		}

		// Set an empty.
		$build  = '';

		// Wrap the product dropdown in a div.
		$build .= '<div class="wc-product-subs-table-filter wc-product-subs-table-filter-products">';

			// Handle our screen reader label.
			$build .= '<label class="screen-reader-text" for="wc-product-subs-product-filter">' . esc_html__( 'Filter by product', 'woo-subscribe-to-products' ) . '</label>';

			// Begin the select dropdown.
			$build .= '<select name="wc-product-subs-product-filter" id="wc-product-subs-product-filter" class="postform">';

				// Load our null value.
				$build .= '<option value="0">' . esc_html__( 'All Products', 'woo-subscribe-to-products' ) . '</option>';

				// Now loop my product IDs and show them.
				foreach ( $enabled_products as $product_id ) {

					// Set our title.
					$pname  = get_the_title( $product_id );

					// And load the dropdown.
					$build .= '<option value="' . absint( $product_id ) . '">' . esc_html( $pname ) . '</option>';
				}

			// Close the select.
			$build .= '</select>';

		// Close the div.
		$build .= '</div>';

		// And return the build.
		if ( ! $echo ) {
			return $build;
		}

		// Echo out the build.
		echo $build;
	}

	/**
	 * Build and display the dropdown for customers.
	 *
	 * @param  boolean $echo  Whether to echo out the markup or return.
	 *
	 * @return HTML
	 */
	protected function customer_filter_dropdown( $echo = true ) {

		// Get our current customers.
		$current_customers  = Queries\get_all_customers();

		// Bail if we don't have any current customers to filter by.
		if ( ! $current_customers ) {
			return;
		}

		// Set an empty.
		$build  = '';

		// Wrap the product dropdown in a div.
		$build .= '<div class="wc-product-subs-table-filter wc-product-subs-table-filter-customers">';

			// Handle our screen reader label.
			$build .= '<label class="screen-reader-text" for="wc-product-subs-customer-filter">' . esc_html__( 'Filter by customer', 'woo-subscribe-to-products' ) . '</label>';

			// Begin the select dropdown.
			$build .= '<select name="wc-product-subs-customer-filter" id="wc-product-subs-customer-filter" class="postform">';

				// Load our null value.
				$build .= '<option value="0">' . esc_html__( 'All Customers', 'woo-subscribe-to-products' ) . '</option>';

				// Now loop my customers and show them.
				foreach ( $current_customers as $customer_id => $customer_data ) {
					$build .= '<option value="' . absint( $customer_id ) . '">' . esc_html( $customer_data['display_name'] ) . '</option>';
				}

			// Close the select.
			$build .= '</select>';

		// Close the div.
		$build .= '</div>';

		// And return the build.
		if ( ! $echo ) {
			return $build;
		}

		// Echo out the build.
		echo $build;
	}

	/**
	 * Return null for our table, since no row actions exist.
	 *
	 * @param  object $item         The item being acted upon.
	 * @param  string $column_name  Current column name.
	 * @param  string $primary      Primary column name.
	 *
	 * @return null
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		return apply_filters( Core\HOOK_PREFIX . 'table_row_actions', '', $item, $column_name, $primary );
 	}

	/**
	 * Define the sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		// Build our array of sortable columns.
		$setup  = array(
			'visible_name'  => array( 'visible_name', false ),
			'product_name'  => array( 'product_name', true ),
			'signup_date'   => array( 'signup_date', true ),
		);

		// Return it, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'table_sortable_columns', $setup );
	}

	/**
	 * Define which columns are hidden.
	 *
	 * @return array
	 */
	public function get_hidden_columns() {
		return apply_filters( Core\HOOK_PREFIX . 'table_hidden_columns', array() );
	}

	/**
	 * Return available bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {

		// Make a basic array of the actions we wanna include.
		$setup  = array(
			'wc_product_subs_unsubscribe' => __( 'Unsubscribe', 'woo-subscribe-to-products' ),
			'wc_product_subs_export'      => __( 'Export', 'woo-subscribe-to-products' )
		);

		// Return it filtered.
		return apply_filters( Core\HOOK_PREFIX . 'table_bulk_actions', $setup );
	}

	/**
	 * Handle bulk actions.
	 *
	 * @see $this->prepare_items()
	 */
	protected function process_bulk_action() {

		// Bail if we aren't on the page.
		if ( empty( $this->current_action() ) || ! in_array( $this->current_action(), array_keys( $this->get_bulk_actions() ) ) ) {
			return;
		}

		// Make sure we have the page we want.
		if ( empty( $_GET['page'] ) || Core\MENU_SLUG !== sanitize_text_field( $_GET['page'] ) ) {
			return;
		}

		// Fail on a missing or bad nonce.
		if ( empty( $_POST['wc_product_subs_nonce_name'] ) || ! wp_verify_nonce( $_POST['wc_product_subs_nonce_name'], 'wc_product_subs_nonce_action' ) ) {
			Helpers\admin_page_redirect( array( 'success' => 0, 'errcode' => 'bad_nonce' ) );
		}

		// Check for the array of relationship IDs being passed.
		if ( empty( $_POST['wc_product_subs_relationship_ids'] ) ) {
			Helpers\admin_page_redirect( array( 'success' => 0, 'errcode' => 'no_ids' ) );
		}

		// Set and sanitize my IDs.
		$relationship_ids   = array_map( 'absint', $_POST['wc_product_subs_relationship_ids'] );

		// Handle my different bulk actions.
		switch ( $this->current_action() ) {

			case 'wc_product_subs_unsubscribe' :
				$this->process_bulk_unsubscribe( $relationship_ids );
				break;

			case 'wc_product_subs_export' :
				$this->process_bulk_export( $relationship_ids );
				break;

			// End all case breaks.
		}

		// Got to the end? Why?
		Helpers\admin_page_redirect( array( 'success' => 0, 'errcode' => 'unknown' ) );
	}

	/**
	 * Handle the unsubscribe bulk action.
	 *
	 * @param  array  $relationship_ids  The IDs requested via bulk.
	 *
	 * @return void
	 */
	private function process_bulk_unsubscribe( $relationship_ids = array() ) {

		// Bail if no relationship IDs were passed.
		if ( empty( $relationship_ids ) ) {
			return false;
		}

		// Now loop and kill each one.
		foreach ( $relationship_ids as $relationship_id ) {
			Database\delete_by_relationship( $relationship_id );
		}

		// If we had customer IDs passed, filter and purge transients.
		if ( ! empty( $_POST['wc_product_subs_customer_ids'] ) ) {
			$this->purge_customer_transients( $_POST['wc_product_subs_customer_ids'] );
		}

		// If we had product IDs passed, filter and purge transients.
		if ( ! empty( $_POST['wc_product_subs_product_ids'] ) ) {
			$this->purge_product_transients( $_POST['wc_product_subs_product_ids'] );
		}

		// Redirect to the success.
		Helpers\admin_page_redirect( array( 'success' => 1, 'action' => 'unsubscribed', 'count' => count( $relationship_ids ) ) );
	}

	/**
	 * Handle the export bulk action.
	 *
	 * @param  array  $relationship_ids  The IDs requested via bulk.
	 *
	 * @return void
	 */
	private function process_bulk_export( $relationship_ids = array() ) {

		// Bail if no relationship IDs were passed.
		if ( empty( $relationship_ids ) ) {
			return false;
		}

		// Add our option key to get later.
		update_option( 'wc_product_subs_export_ids', $relationship_ids, 'no' );

		// Set the nonce for the export.
		$nonce  = wp_create_nonce( 'wc_product_subs_export' );

		// Redirect to trigger the export function.
		Helpers\admin_page_redirect( array( 'wc_product_subs_export' => 1, 'nonce' => esc_attr( $nonce ) ), false );
	}

	/**
	 * Delete the transients for customer IDs.
	 *
	 * @param  array $customer_ids  The array of customer IDs we have.
	 *
	 * @return void
	 */
	protected function purge_customer_transients( $customer_ids = array() ) {

		// First sanitize, then filter.
		$customer_ids   = array_map( 'absint', $customer_ids );
		$customer_ids   = array_unique( $customer_ids );

		// Now loop and purge.
		foreach ( $customer_ids as $customer_id ) {
			delete_transient( 'woo_customer_subscribed_products_' . absint( $customer_id ) );
		}
	}

	/**
	 * Delete the transients for product IDs.
	 *
	 * @param  array $product_ids  The array of product IDs we have.
	 *
	 * @return void
	 */
	protected function purge_product_transients( $product_ids = array() ) {

		// First sanitize, then filter.
		$product_ids    = array_map( 'absint', $product_ids );
		$product_ids    = array_unique( $product_ids );

		// Now loop and purge.
		foreach ( $product_ids as $product_id ) {
			delete_transient( 'woo_product_subscribed_customers_' . absint( $product_id ) );
		}
	}

	/**
	 * Checkbox column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_cb( $item ) {

		// Set my ID.
		$id = absint( $item['id'] );

		// Return my checkbox.
		return '<input type="checkbox" name="wc_product_subs_relationship_ids[]" class="wc-product-subscriptions-admin-checkbox" id="cb-' . $id . '" value="' . $id . '" /><label for="cb-' . $id . '" class="screen-reader-text">' . __( 'Select subscription', 'woo-subscribe-to-products' ) . '</label>';
	}

	/**
	 * The visible name column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_visible_name( $item ) {

		// Build my markup.
		$setup  = '';

		// Set the display name.
		$setup .= '<span class="wc-product-subscriptions-admin-table-display wc-product-subscriptions-admin-table-name">';
			$setup .= '<strong>' . esc_html( $item['customer_name'] ) . '</strong>';
		$setup .= '</span>';

		// Add a hidden field with the value.
		$setup .= '<input type="hidden" name="wc_product_subs_customer_ids[]" value="' . absint( $item['customer_id'] ) . '">';

		// Create my formatted date.
		$setup  = apply_filters( Core\HOOK_PREFIX . 'column_visible_name', $setup, $item );

		// Return, along with our row actions.
		return $setup . $this->row_actions( $this->setup_row_action_items( $item ) );
	}

	/**
	 * The product name column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_product_name( $item ) {

		// Build my markup.
		$setup  = '';

		// Set the product name name.
		$setup .= '<span class="wc-product-subscriptions-admin-table-display wc-product-subscriptions-admin-table-product-name">';
			$setup .= '<strong>' . esc_html( $item['product_name'] ) . '</strong>';
		$setup .= '</span>';

		// Include the various product links.
		$setup .= '<div class="row-actions wc-product-subscriptions-admin-table-actions">';

			// Show the view link.
			$setup .= '<a title="' . __( 'View Product', 'woo-subscribe-to-products' ) . '" href="' . esc_url( $item['product_link'] ) . '">' . esc_html__( 'View Product', 'woo-subscribe-to-products' ) . '</a>';

			$setup .= '&nbsp;|&nbsp;';

			// Show the edit link.
			$setup .= '<a title="' . __( 'Edit Product', 'woo-subscribe-to-products' ) . '" href="' . esc_url( $item['product_edit'] ) . '">' . esc_html__( 'Edit Product', 'woo-subscribe-to-products' ) . '</a>';

			// Add a hidden field with the value.
			$setup .= '<input type="hidden" name="wc_product_subs_product_ids[]" value="' . absint( $item['product_id'] ) . '">';

		// And close the div.
		$setup .= '</div>';

		// Return my formatted date.
		return apply_filters( Core\HOOK_PREFIX . 'column_product_name', $setup, $item );
	}

	/**
	 * The signup date column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_signup_date( $item ) {

		// Grab the desired date foramtting.
		$format = apply_filters( Core\HOOK_PREFIX . 'column_date_format', get_option( 'date_format', 'Y-m-d' ) );

		// Set the date to a stamp.
		$stamp  = strtotime( $item['signup_date'] );

		// Get my relative date.
		$show   = sprintf( _x( '%s ago', '%s = human-readable time difference', 'woo-subscribe-to-products' ), human_time_diff( $stamp, current_time( 'timestamp', 1 ) ) );

		// Build my markup.
		$setup  = '';

		// Set the product name name.
		$setup .= '<span class="wc-product-subscriptions-admin-table-display wc-product-subscriptions-admin-table-signup-date">';
			$setup .= date( $format, $stamp ) . '<br>';
			$setup .= '<small><em>' . esc_html( $show ) . '</em></small>';
		$setup .= '</span>';

		// Return my formatted date.
		return apply_filters( Core\HOOK_PREFIX . 'column_signup_date', $setup, $item );
	}

	/**
	 * Our column with eventual actions we might have.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_action_list( $item ) {

		// This is clearly a placeholder.
		return 'This is here until other things are.';
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data() {

		// Get all the relationship data.
		$relationships  = Queries\get_all_subscription_data();

		// Bail with no data.
		if ( ! $relationships ) {
			return array();
		}

		// Set my empty.
		$data   = array();

		// Now loop each customer info.
		foreach ( $relationships as $relationship_id => $relationship_data ) {

			// Set my order args.
			$order_args = array( 'post_type' => 'shop_order', 'post_status' => 'all', '_customer_user' => absint( $relationship_data['customer_id'] ) );

			// Set the array of the data we want.
			$setup  = array(
				'id'              => absint( $relationship_id ),
				'product_id'      => absint( $relationship_data['product_id'] ),
				'product_name'    => esc_attr( $relationship_data['product']['post_title'] ),
				'product_edit'    => get_edit_post_link( absint( $relationship_data['product_id'] ), 'raw' ),
				'product_link'    => get_permalink( absint( $relationship_data['product_id'] ) ),
				'customer_id'     => absint( $relationship_data['customer_id'] ),
				'customer_name'   => esc_attr( $relationship_data['customer']['display_name'] ),
				'customer_edit'   => get_edit_user_link( absint( $relationship_data['product_id'] ), 'raw' ),
				'customer_email'  => esc_attr( $relationship_data['customer']['user_email'] ),
				'customer_orders' => add_query_arg( $order_args, admin_url( 'edit.php' ) ),
				'signup_date'     => esc_attr( $relationship_data['signup'] ),
			);

			// Run it through a filter.
			$data[] = apply_filters( Core\HOOK_PREFIX . 'table_data_item', $setup, $relationship_id, $relationship_data );
		}

		// Return our data.
		return apply_filters( Core\HOOK_PREFIX . 'table_data_array', $data, $relationships );
	}

	/**
	 * Take the default dataset and filter it.
	 *
	 * @param  array  $dataset  The current dataset we have.
	 *
	 * @return array
	 */
	protected function maybe_filter_dataset( $dataset = array() ) {

		// Return the dataset we got if we don't have the submit.
		if ( empty( $_POST['wc-product-subs-filter-submit' ] ) ) {
			return $dataset;
		}

		// Make sure we have the page we want.
		if ( empty( $_GET['page'] ) || Core\MENU_SLUG !== sanitize_text_field( $_GET['page'] ) ) {
			return $dataset;
		}

		// Fail on a missing or bad nonce.
		if ( empty( $_POST['wc_product_subs_nonce_name'] ) || ! wp_verify_nonce( $_POST['wc_product_subs_nonce_name'], 'wc_product_subs_nonce_action' ) ) {
			Helpers\admin_page_redirect( array( 'success' => 0, 'errcode' => 'bad_nonce' ) );
		}

		// Handle a product ID filter.
		if ( ! empty( $_POST['wc-product-subs-product-filter'] ) && 'product' === get_post_type( absint( $_POST['wc-product-subs-product-filter'] ) ) ) {
			$dataset    = $this->filter_dataset_by_id( $dataset, absint( $_POST['wc-product-subs-product-filter'] ), 'product_id' );
		}

		// Handle a customer ID filter.
		if ( ! empty( $_POST['wc-product-subs-customer-filter'] ) ) {
			$dataset    = $this->filter_dataset_by_id( $dataset, absint( $_POST['wc-product-subs-customer-filter'] ), 'customer_id' );
		}

		// And return the dataset, however we have it.
		return $dataset;
	}

	/**
	 * Filter out the dataset by ID.
	 *
	 * @param  array   $dataset  The dataset we wanna filter.
	 * @param  integer $id       The specific ID we wanna check for.
	 * @param  string  $type     Which ID type. Either 'product_id', 'customer_id', or 'id'.
	 *
	 * @return array
	 */
	private function filter_dataset_by_id( $dataset = array(), $id = 0, $type = '' ) {

		// Bail without a dataset, ID, or type.
		if ( empty( $dataset ) || empty( $id ) || empty( $type ) ) {
			return;
		}

		// Loop the dataset.
		foreach ( $dataset as $index => $values ) {

			// If we do not have a match, unset it and go about our day.
			if ( absint( $id ) !== absint( $values[ $type ] ) ) {
				unset( $dataset[ $index ] );
			}
		}

		// Return thge dataset, with the array keys reset.
		return ! empty( $dataset ) ? array_values( $dataset ) : array();
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  array  $dataset      Our entire dataset.
	 * @param  string $column_name  Current column name
	 *
	 * @return mixed
	 */
	public function column_default( $dataset, $column_name ) {

		// Run our column switch.
		switch ( $column_name ) {

			case 'display_name' :
			case 'product_name' :
			case 'signup_date' :
			case 'action_list' :
				return ! empty( $dataset[ $column_name ] ) ? $dataset[ $column_name ] : '';

			default :
				return apply_filters( Core\HOOK_PREFIX . 'table_column_default', '', $dataset, $column_name );
		}
	}

	/**
	 * Handle the single row action.
	 *
	 * @return void
	 */
	protected function process_single_action() {
		// There will likely be something here.
	}

	/**
	 * Create the row actions we want.
	 *
	 * @param  array $item  The item from the dataset.
	 *
	 * @return array
	 */
	private function setup_row_action_items( $item ) {

		// Set up our array of items.
		$setup = array(

			'view'   => '<a class="wc-product-subscriptions-admin-table-link wc-product-subscriptions-admin-table-link-view" title="' . __( 'View Customer', 'woo-subscribe-to-products' ) . '" href="' . esc_url( $item['customer_edit'] ) . '">' . esc_html( 'View Customer', 'woo-subscribe-to-products' ) . '</a>',

			'orders' => '<a class="wc-product-subscriptions-admin-table-link wc-product-subscriptions-admin-table-link-orders" title="' . __( 'View Orders', 'woo-subscribe-to-products' ) . '" href="' . esc_url( $item['customer_orders'] ) . '">' . esc_html( 'View Orders', 'woo-subscribe-to-products' ) . '</a>',

			'email'  => '<a class="wc-product-subscriptions-admin-table-link wc-product-subscriptions-admin-table-link-email" title="' . __( 'Email Customer', 'woo-subscribe-to-products' ) . '" href="' . esc_url( 'mailto:' . antispambot( $item['customer_email'] ) ) . '">' . esc_html( 'Email Customer', 'woo-subscribe-to-products' ) . '</a>',
		);

		// Return our row actions.
		return apply_filters( Core\HOOK_PREFIX . 'table_row_actions', $setup, $item );
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data( $a, $b ) {

		// Set defaults and check for query strings.
		$ordby  = ! empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'signup_date';
		$order  = ! empty( $_GET['order'] ) ? $_GET['order'] : 'asc';

		// Set my result up.
		$result = strcmp( $a[ $ordby ], $b[ $ordby ] );

		// Return it one way or the other.
		return 'asc' === $order ? $result : -$result;
	}
}
