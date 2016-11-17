<?php
/**
 * AJAX functions
 *
 * @package		EDD\Wallet\AJAX
 * @since		1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Process incentives
 *
 * @since 1.0.0
 * @return void
 */
function edd_wallet_process_incentive() {
	if( $_REQUEST['gateway'] == 'wallet' ) {
		EDD()->session->set( 'wallet_has_incentives', '1' );
	} else {
		EDD()->session->set( 'wallet_has_incentives', null );
	}

	// Refresh the cart
	if ( empty( $_POST['billing_country'] ) ) {
		$_POST['billing_country'] = edd_get_shop_country();
	}

	ob_start();
	edd_checkout_cart();
	$cart = ob_get_clean();
	$response = array(
		'html'         => $cart,
		'tax_raw'      => edd_get_cart_tax(),
		'tax'          => html_entity_decode( edd_cart_tax( false ), ENT_COMPAT, 'UTF-8' ),
		'tax_rate_raw' => edd_get_tax_rate(),
		'tax_rate'     => html_entity_decode( edd_get_formatted_tax_rate(), ENT_COMPAT, 'UTF-8' ),
		'total'        => html_entity_decode( edd_cart_total( false ), ENT_COMPAT, 'UTF-8' ),
		'total_raw'    => edd_get_cart_total(),
	);

	echo json_encode( $response );

	edd_die();
}
add_action( 'wp_ajax_edd_wallet_process_incentive', 'edd_wallet_process_incentive' );
add_action( 'wp_ajax_nopriv_edd_wallet_process_incentive', 'edd_wallet_process_incentive' );


/**
 * Process apply to cart
 *
 * @since 1.0.0
 * @return void
 */
function edd_wallet_process_apply() {
	$value      = (float) $_REQUEST['value'];
	$total      = (float) edd_get_cart_total();
	$fee_amount = ( $value < $total ? $value : $total );

	$fee = array(
		'amount'        => - absint( $fee_amount ),
		'label'         => edd_get_option( 'edd_wallet_cart_funds_label', __( 'Wallet Funds', 'edd-wallet' ) ),
		'type' => 'item',
		'no_tax'        => true,
		'id'            => 'edd-wallet-funds'
	);

	EDD()->fees->add_fee( $fee );

	// Refresh the cart
	if ( empty( $_POST['billing_country'] ) ) {
		$_POST['billing_country'] = edd_get_shop_country();
	}

	ob_start();
	edd_checkout_cart();
	$cart = ob_get_clean();
	$response = array(
		'html'         => $cart,
		'tax_raw'      => edd_get_cart_tax(),
		'tax'          => html_entity_decode( edd_cart_tax( false ), ENT_COMPAT, 'UTF-8' ),
		'tax_rate_raw' => edd_get_tax_rate(),
		'tax_rate'     => html_entity_decode( edd_get_formatted_tax_rate(), ENT_COMPAT, 'UTF-8' ),
		'total'        => html_entity_decode( edd_cart_total( false ), ENT_COMPAT, 'UTF-8' ),
		'total_raw'    => edd_get_cart_total(),
	);

	echo json_encode( $response );

	edd_die();
}
add_action( 'wp_ajax_edd_wallet_process_apply', 'edd_wallet_process_apply' );
add_action( 'wp_ajax_nopriv_edd_wallet_process_apply', 'edd_wallet_process_apply' );
