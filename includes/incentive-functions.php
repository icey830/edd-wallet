<?php
/**
 * Incentive functions
 *
 * @package		EDD\Wallet\Incentives
 * @since		1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Incentives shouldn't be used on non-wallet purchases!
 *
 * @since		1.0.0
 * @return		void
 */
function edd_wallet_maybe_remove_incentive() {
	if( ! isset( $_GET['payment-mode'] ) || $_GET['payment-mode'] !== 'wallet' ) {
		if( EDD()->session->get( 'wallet_has_incentives' ) ){
			EDD()->session->set( 'wallet_has_incentives', null );
		}
	}
}
//add_action( 'edd_before_checkout_cart', 'edd_wallet_maybe_remove_incentive' );



/**
 * Maybe add incentive discounts
 *
 * @since		1.0.1
 * @param		float $discount The current discount amount
 * @param		array $item The cart item array
 * @return		float $discount The updated discount amount
 */
function edd_wallet_item_incentive_amount( $discount, $item ) {
	$incentive_amount = edd_get_option( 'edd_wallet_incentive_amount', 0 );

	if( $incentive_amount <= 0 ) {
		return $discount;
	}

	if( ! EDD()->session->get( 'wallet_has_incentives' ) ) {
		return $discount;
	}

	if( edd_has_variable_prices( $item['id'] ) ) {
		$prices   = edd_get_variable_prices( $item['id'] );
		$price_id = ( isset( $item['options']['price_id'] ) ) ? $item['options']['price_id'] : 0;

		if( $price_id !== false && $price_id !== '' && isset( $prices[$price_id] ) ) {
			$price = edd_get_price_option_amount( $item['id'], $price_id );
		} else {
			$price = edd_get_lowest_price_option( $item['id'] );
		}
	} else {
		$price = edd_get_download_price( $item['id'] );
	}

	$incentive_type = edd_get_option( 'edd_wallet_incentive_type', 'flatrate' );

	if( $incentive_type == 'percent' ) {
		if( $incentive_amount > 1 ) {
			$incentive_amount /= 100;
		}

		$incentive_amount = ( $price * $incentive_amount );
		$incentive_amount = number_format( $incentive_amount, 2, '.', '' );
	}

	$discount += $incentive_amount;

	return $discount;
}
add_filter( 'edd_get_cart_content_details_item_discount_amount', 'edd_wallet_item_incentive_amount', 10, 2 );