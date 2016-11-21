<?php
/**
 * Filters
 *
 * @package     EDD\Wallet\Filters
 * @since       2.0.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


function edd_wallet_discount_amount( $discount, $item ) {
	global $edd_is_last_cart_item, $edd_wallet_discount;

	$amount = 0;
	$wallet = EDD()->session->get( 'wallet_applied' );

	if ( $wallet ) {
		$items_subtotal   = 0.00;
		$cart_items       = edd_get_cart_contents();
		$price            = edd_get_cart_item_price( $item['id'], $item['options'] );
		$discounted_price = $price;

		foreach ( $cart_items as $cart_item ) {
			$item_price      = edd_get_cart_item_price( $cart_item['id'], $cart_item['options'] );
			$items_subtotal += $item_price * $cart_item['quantity'];
		}

		$subtotal_percent   = ( ( $price * $item['quantity'] ) / $items_subtotal );
		$discounted_amount  = $wallet['applied_amount'] * $subtotal_percent;
		$discounted_price  -= $discounted_amount;

		$edd_wallet_discount += round( $discounted_amount, edd_currency_decimal_filter() );

		if ( $edd_is_last_cart_item && $edd_wallet_discount < $wallet['applied_amount'] ) {
			$adjustment        = $wallet['applied_amount'] - $edd_wallet_discount;
			$discounted_price -= $adjustment;
		}

		if ( $discounted_price < 0 ) {
			$discounted_price = 0;
		}

		$amount = ( $price - $discounted_price );

		// Add the discount details to the session
		if( ! array_key_exists( 'wallet_discounts', $wallet ) ) {
			$wallet['wallet_discounts'] = array();
		}
		$wallet['wallet_discounts'][ $item['id'] ] = $amount;
		EDD()->session->set( 'wallet_applied', $wallet );

		if ( $edd_is_last_cart_item ) {
			$edd_wallet_discount = 0.00;
		}
	}

	return $amount;
}
add_filter( 'edd_get_cart_content_details_item_discount_amount', 'edd_wallet_discount_amount', 20, 2 );
