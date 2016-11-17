<?php
/**
 * Actions
 *
 * @package     EDD\Wallet\Actions
 * @since       2.0.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Display a fieldset for Wallet on the checkout page
 *
 * @since       2.0.0
 * @return      void
 */
function edd_wallet_display_cart_row() {
	if ( is_user_logged_in() ) {
		// Get the current user
		$user_id = get_current_user_id();

		// Get the wallet value
		$value = edd_wallet()->wallet->balance( $user_id );

		// Get the cart total
		$total = edd_get_cart_total();

		$checkout_label = edd_get_option( 'edd_wallet_cart_label', __( 'My Wallet', 'edd-wallet' ) );

		if ( edd_get_option( 'edd_wallet_show_value_in_cart', false ) ) {
			$checkout_label .= sprintf( __( ' (%s available)', 'edd-wallet' ), edd_currency_filter( edd_format_amount( $value ) ) );
		}

		// Hide if funds already applied or insufficient
		$fee           = EDD()->fees->get_fee( 'edd-wallet-funds' );
		$allow_partial = edd_get_option( 'edd_wallet_allow_partial', false ) ? true : false;

		if ( ( ( $allow_partial && $value != 0 && ! $fee ) || ( ! $allow_partial && $value >= $total && ! $fee ) ) && $total != 0 ) {
			$action = edd_get_option( 'edd_wallet_cart_action_label', __( 'Apply to purchase', 'edd-wallet' ) );
			?>
			<tr class="edd_cart_wallet_row">
				<td colspan="2" class="edd_cart_wallet_label"><?php echo $checkout_label; ?></td>
				<td>
					<a href="#" id="edd_wallet_apply_funds" data-wallet-value="<?php echo $value; ?>"><?php echo $action; ?></a>
				</td>
			</tr>
			<?php
		}
	}
}
add_action( 'edd_cart_items_after', 'edd_wallet_display_cart_row' );


/**
 * Process purchases/refunds
 *
 * @since       2.0.0
 * @param       int $payment_id The ID of a payment
 * @param       string $new_status The new status of the payment
 * @param       string $old_status The old status of the payment
 * @return      void
 */
function edd_wallet_process_transaction( $payment_id, $new_status, $old_status ) {
	$payment = new EDD_Payment( $payment_id );

	if ( $old_status == 'pending' ) {
		$fees        = $payment->get_fees();
		$used_wallet = false;

		if ( is_array( $fees ) && count( $fees ) > 0 ) {
			foreach ( $fees as $id => $fee ) {
				if ( $fee['id'] == 'edd-wallet-funds' ) {
					$used_wallet = $id;
					continue;
				}
			}
		}

		if( $used_wallet !== false ) {
			$user_id = edd_get_payment_user_id( $payment_id );
			$amount  = absint( $fees[ $used_wallet ]['amount'] );

			// Withdraw the funds
			edd_wallet()->wallet->withdraw( $user_id, $amount, 'withdrawal', $payment_id );

			// Insert payment note
			edd_insert_payment_note( $payment_id, sprintf( __( '%s withdrawn from Wallet.', 'edd-wallet' ), edd_currency_filter( edd_format_amount( $amount ) ) ) );
		}
	} elseif ( ( $old_status == 'publish' || $old_status == 'revoked' ) && $new_status == 'refunded' ) {
		$fees         = $payment->get_fees();
		$used_wallet  = false;
		$used_gateway = edd_get_payment_gateway( $payment_id ) == 'wallet' ? true : false;

		if ( ! $used_gateway && is_array( $fees ) && count( $fees ) > 0 ) {
			foreach ( $fees as $id => $fee ) {
				if ( $fee['id'] == 'edd-wallet-funds' ) {
					$used_wallet = $id;
					continue;
				}
			}
		}

		if ( $used_wallet !== false || $used_gateway ) {
			$user_id       = edd_get_payment_user_id( $payment_id );

			if ( $used_gateway ) {
				$refund_amount = edd_get_payment_amount( $payment_id );
			} else {
				$refund_amount = absint( $fees[ $used_wallet ]['amount'] );
			}

			// Deposit the funds
			edd_wallet()->wallet->deposit( $user_id, $refund_amount, 'refund' );

			// Insert payment note
			edd_insert_payment_note( $payment_id, __( 'Refund completed to Wallet.', 'edd-wallet' ) );
		}
	}
}
add_action( 'edd_update_payment_status', 'edd_wallet_process_transaction', 200, 3 );
