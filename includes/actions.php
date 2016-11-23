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
		$value              = edd_wallet_get_user_value();
		$total              = edd_get_cart_total();
		$apply_funds_label  = edd_get_option( 'edd_wallet_cart_label', __( 'My Wallet', 'edd-wallet' ) );
		$remove_funds_label = edd_get_option( 'edd_wallet_cart_remove_label', __( 'Wallet Funds Applied', 'edd-wallet' ) );

		if ( edd_get_option( 'edd_wallet_show_value_in_cart', false ) ) {
			$apply_funds_label .= sprintf( __( ' (%s available)', 'edd-wallet' ), edd_currency_filter( edd_format_amount( $value ) ) );
		}

		// Hide if funds already applied or insufficient
		$wallet        = EDD()->session->get( 'wallet_applied' );
		$allow_partial = edd_get_option( 'edd_wallet_allow_partial', false ) ? true : false;
		$label         = ( $wallet ? $remove_funds_label : $apply_funds_label );
		$class         = ( $wallet ? ' edd_cart_wallet_row_applied': '' );

		if ( ( ( $allow_partial && $value != 0 ) || ( ! $allow_partial && $value >= $total ) ) && $total != 0 ) {
			$action = edd_get_option( 'edd_wallet_cart_action_label', __( 'Apply to purchase', 'edd-wallet' ) );
			?>
			<tr class="edd_cart_wallet_row<?php echo $class; ?>">
				<td colspan="2" class="edd_cart_wallet_label"><?php echo $label; ?></td>
				<td>
					<?php if ( ! $wallet ) : ?>
						<a href="#" id="edd_wallet_apply_funds" data-wallet-action="apply"><?php echo $action; ?></a>
					<?php else : ?>
						<a href="#" id="edd_wallet_apply_funds" data-wallet-action="remove"><?php _e( 'Remove', 'edd-wallet' ); ?></a>
					<?php endif; ?>
				</td>
			</tr>
			<?php
		} elseif ( $wallet && $total == 0 ) {
			?>
			<tr class="edd_cart_wallet_row<?php echo $class; ?>">
				<td colspan="2" class="edd_cart_wallet_label"><?php echo $label; ?></td>
				<td>
					<a href="#" id="edd_wallet_apply_funds" data-wallet-action="remove"><?php _e( 'Remove', 'edd-wallet' ); ?></a>
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
 * @todo        This needs to be updated for the new non-gateway/fees model. Remember that we have to take past gateway-based transactions into account for refunds.
 */
function edd_wallet_process_transaction( $payment_id, $new_status, $old_status ) {
	$payment     = new EDD_Payment( $payment_id );
	$used_wallet = 0;
	// Check for wallet as fees
	if ( is_array( $payment->fees ) && count( $payment->fees ) > 0 ) {
		foreach ( $payment->fees as $id => $fee ) {
			if ( $fee['id'] == 'edd-wallet-funds' ) {
				$used_wallet = $fee['amount'];
				continue;
			}
		}
	}

	if ( empty( $used_wallet ) ) { // Check for them as options (since issue/7)
		foreach ( $payment->cart_details as $item ) {
			if ( ! empty( $item['item_number']['options']['wallet_amount'] ) ) {
				$used_wallet += $item['item_number']['options']['wallet_amount'];
			}
		}
	}

	if ( ! empty( $used_wallet ) ) {
		$user_id = $payment->user_id;
		$amount  = (float) $used_wallet;

		if ( $old_status == 'pending' ) {
			// Withdraw the funds
			edd_wallet()->wallet->withdraw( $user_id, $amount, 'withdrawal', $payment_id );
			// Insert payment note
			edd_insert_payment_note( $payment_id, sprintf( __( '%s withdrawn from Wallet.', 'edd-wallet' ), edd_currency_filter( edd_format_amount( $amount ) ) ) );
		} elseif ( ( $old_status == 'publish' || $old_status == 'revoked' ) && $new_status == 'refunded' ) {
			// Deposit the funds
			edd_wallet()->wallet->deposit( $user_id, $amount, 'refund' );
			// Insert payment note
			edd_insert_payment_note( $payment_id, __( 'Refund completed to Wallet.', 'edd-wallet' ) );
		}
	}
}
add_action( 'edd_update_payment_status', 'edd_wallet_process_transaction', 200, 3 );



/**
 * Add applied amounts to individual cart items
 *
 * @since       2.0.0
 * @param       array $item The cart item data
 * @param       int $key The cart item key
 * @return      void
 * @todo        I think this will fail in situations where mult-item mode is enabled. Fix it!
 */
function edd_wallet_display_cart_item( $item, $key ) {
	$wallet = EDD()->session->get( 'wallet_applied' );

	if( $wallet ) {
		$label = edd_get_option( 'edd_wallet_funds_applied_label', __( 'Wallet Funds Applied', 'edd-wallet' ) );
		?>
		<p class="edd-wallet-applied-discount"><span><?php echo $label; ?>:</span> <?php echo edd_currency_filter( edd_format_amount( $wallet['wallet_discounts'][ $key ] ) ); ?></p>
		<?php
	}
}
add_action( 'edd_checkout_cart_item_title_after', 'edd_wallet_display_cart_item', 20, 2 );

function edd_wallet_process_remove_from_cart( $cart_key, $item_id ) {
	$wallet = EDD()->session->get( 'wallet_applied' );

	if ( ! empty( $wallet ) ) {
		unset( $wallet['wallet_discounts'][ $cart_key ] );
	}

	EDD()->session->set( 'wallet_applied', $wallet );
}
add_action( 'edd_post_remove_from_cart', 'edd_wallet_process_remove_from_cart', 1, 2 );