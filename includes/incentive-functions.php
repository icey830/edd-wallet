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
	if( ! function_exists( 'edd_is_checkout' ) ) {
		return;
	}

	if( edd_get_chosen_gateway() !== 'wallet' ) {
		EDD()->session->set( 'wallet_has_incentives', null );
	}
}
add_action( 'init', 'edd_wallet_maybe_remove_incentive' );



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

		$incentive_amount /= 100;

		$incentive_amount = ( $price * $incentive_amount );

		if( edd_item_quantities_enabled() && edd_get_option( 'edd_wallet_incentive_quantities', false ) ) {
			$incentive_amount *= $item['quantity'];
		}

		$incentive_amount = number_format( $incentive_amount, 2, '.', '' );
	} else {
		if( edd_item_quantities_enabled() && edd_get_option( 'edd_wallet_incentive_quantities', false ) ) {
			$incentive_amount *= $item['quantity'];
		}
	}

	$discount += $incentive_amount;

	return $discount;
}
add_filter( 'edd_get_cart_content_details_item_discount_amount', 'edd_wallet_item_incentive_amount', 10, 2 );

/**
 * Displays the incentive discount row on the cart
 *
 * @since		1.0.1
 * @return		void
 */
function edd_wallet_cart_items_renewal_row() {

	$incentive_type        = edd_get_option( 'edd_wallet_incentive_type', 'flatrate' );
	$incentive_amount      = edd_get_option( 'edd_wallet_incentive_amount', 0 );
	$incentive_description = edd_get_option( 'edd_wallet_incentive_description', __( 'Wallet Discount', 'edd-wallet' ) );

	if( $incentive_amount <= 0 ) {
		return;
	}

	if( ! EDD()->session->get( 'wallet_has_incentives' ) ) {
		return;
	}

	if( $incentive_type == 'percent' ) {
		$discount = $incentive_amount . '%';
	} else {
		$discount = edd_currency_filter( edd_sanitize_amount( $incentive_amount * edd_get_cart_quantity() ) );
	}
?>
	<tr class="edd_cart_footer_row edd_wallet_incentive_row">
		<td colspan="3"><?php printf( __( '%1$s: %2$s', 'edd-wallet' ), $incentive_description, $discount ); ?></td>
	</tr>
<?php
}
add_action( 'edd_cart_items_after', 'edd_wallet_cart_items_renewal_row' );