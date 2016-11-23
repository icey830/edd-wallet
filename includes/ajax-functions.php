<?php
/**
 * AJAX functions
 *
 * @package     EDD\Wallet\AJAX
 * @since       1.0.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Process incentives
 *
 * @since 1.0.0
 * @return void
 */
function edd_wallet_process_incentive() {
	if ( $_REQUEST['gateway'] == 'wallet' ) {
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
	$allow_partial = edd_get_option( 'edd_wallet_allow_partial', false ) ? true : false;
	$cart_total    = edd_get_cart_total();
	$wallet_value  = edd_wallet_get_user_value();

	if ( $_REQUEST['wallet_action'] == 'apply' ) {
		$cart_eligible = $cart_total != 0 && ( ( $allow_partial && $wallet_value != 0 ) || ( ! $allow_partial && $wallet_value >= $cart_total ) ) ? true : false;

		if ( $cart_eligible ) {
			$wallet = array(
				'cart_total'     => $cart_total,
				'wallet_value'   => $wallet_value,
				'applied_amount' => ( ( $allow_partial && $wallet_value < $cart_total ) ? $wallet_value : $cart_total ),
			);

			EDD()->session->set( 'wallet_applied', $wallet );
		}
	} else {
		EDD()->session->set( 'wallet_applied', null );
		$cart = EDD()->session->get( 'edd_cart' );
		foreach ( $cart as $key => &$item ) {
			if ( isset( $item['options']['wallet_amount'] ) ) {
				unset( $item['wallet_amount'] );
				$item['options']['recurring']['trial_period'] = false;
			}
		}
		EDD()->session->set( 'edd_cart', $cart );
	}

	// Refresh the cart
	if ( empty( $_POST['billing_country'] ) ) {
		$_POST['billing_country'] = edd_get_shop_country();
	}

	ob_start();
	edd_checkout_cart();
	$cart_html = ob_get_clean();
	$response  = array(
		'html'         => $cart_html,
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
