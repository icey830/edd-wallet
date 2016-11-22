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
		$cart_item_position = edd_get_item_position_in_cart( $item['id'], $item['options'] );

		$cart = EDD()->session->get( 'edd_cart' );
		$cart[ $cart_item_position ]['options']['wallet_amount'] = $amount;
		$item_price    = edd_get_cart_item_price( $item['id'], $item['options'] );
		$trial_applied = false;

		if ( empty( $item_price - $amount ) ) {

			if ( function_exists( 'EDD_Recurring' ) ) {
				$has_variable_pricing = edd_has_variable_prices( $item['id'] );
				if ( $has_variable_pricing ) {
					$is_recurring = EDD_Recurring()->is_price_recurring( $item['id'], $item['options']['price_id'] );
				} else {
					$is_recurring = EDD_Recurring()->is_recurring( $item['id'] );
				}

				if ( $is_recurring ) {
					if ( $has_variable_pricing ) {
						$period = EDD_Recurring()->get_period( $item['options']['price_id'], $item['id'] );
					} else {
						$period = EDD_Recurring()->get_period_single( $item['id'] );
					}

					$cart[ $cart_item_position ]['options']['recurring']['trial_period']['quantity'] = 1;
					$cart[ $cart_item_position ]['options']['recurring']['trial_period']['period']   = $period;

					$trial_applied = true;
				}
			}

		}

		EDD()->session->set( 'edd_cart', $cart );

		$wallet['wallet_discounts'][ $cart_item_position ]       = $amount;
		EDD()->session->set( 'wallet_applied', $wallet );

		if ( $trial_applied ) {
			$amount = $discount;
		}

		if ( $edd_is_last_cart_item ) {
			$edd_wallet_discount = 0.00;
		}
	}

	return $amount;
}
add_filter( 'edd_get_cart_content_details_item_discount_amount', 'edd_wallet_discount_amount', 20, 2 );

function edd_wallet_maybe_has_trial( $has_trial, $download_id ) {
	$cart = edd_get_cart_content_details();
	foreach ( $cart as $key => $item ) {
		if ( $item['id'] != $download_id ) {
			continue;
		}

		if ( ! empty( $item['item_number']['options']['recurring']['trial_period'] ) ) {
			$has_trial = true;
		}
	}

	return $has_trial;
}
add_filter( 'edd_recurring_download_has_free_trial', 'edd_wallet_maybe_has_trial', 10, 2 );
