<?php
/**
 * Extend the EDD customer table
 *
 * @package     EDD\Wallet\Admin\Customers\Table
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add our column to the table
 *
 * @since       1.0.0
 * @param       array $columns The existing columns
 * @return      array $columns The updated columns
 */
function edd_wallet_customer_columns( $columns ) {
	// Store the date column and remove it
	$date = array_slice( $columns, -1, 1, true );
	array_pop( $columns );

	// Add the wallet column
	$columns['wallet'] = __( 'Wallet', 'edd-wallet' );

	// Re-add the date column
	$columns = array_merge( $columns, $date );

	return $columns;
}
add_filter( 'edd_report_customer_columns', 'edd_wallet_customer_columns' );


/**
 * Display column contents
 *
 * @since       1.0.0
 * @param       string $value The default value for the column
 * @param       int $item_id The ID of the row item
 * @return      string $value The updated value for the column
 */
function edd_wallet_column_data( $value, $item_id ) {

	$customer = new EDD_Customer( $item_id );

	if( $customer->user_id < 1 ) {
		return '';
	}

	$value = edd_wallet()->wallet->balance( $customer->user_id );
	$value = edd_currency_filter( edd_format_amount( (float) $value ) );

	// Build the wallet link
	$value = '<a href="' . admin_url( 'edit.php?post_type=download&page=edd-customers&view=wallet&id=' . $item_id ) . '" title="' . __( 'View user wallet', 'edd-wallet' ) . '">' . $value . '</a>';

	return $value;
}
add_filter( 'edd_customers_column_wallet', 'edd_wallet_column_data', 10, 2 );
