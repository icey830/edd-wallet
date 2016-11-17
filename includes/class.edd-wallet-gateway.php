<?php
/**
 * Wallet pseudo-gateway
 *
 * @package     EDD\Wallet\Gateway
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main EDD_Wallet_Gateway class
 *
 * @since       1.0.0
 */
class EDD_Wallet_Gateway {


	/**
	 * Get things started
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function __construct() {
		// Process payment
		add_action( 'edd_gateway_wallet', array( $this, 'process_payment' ) );

		// Process refunds
		add_action( 'edd_update_payment_status', array( $this, 'process_refund' ), 200, 3 );

		// Add Wallet row to cart
		add_action( 'edd_cart_items_after', array( $this, 'display_cart_row' ) );
	}


	/**
	 * Display a fieldset for Wallet on the checkout page
	 *
	 * @since       1.2.0
	 * @return      void
	 */
	public function display_cart_row() {
		if( is_user_logged_in() ) {
			// Get the current user
			$user_id = get_current_user_id();

			// Get the wallet value
			$value = edd_wallet()->wallet->balance( $user_id );

			// Get the cart total
			$total = edd_get_cart_total();

			$checkout_label = edd_get_option( 'edd_wallet_cart_label', __( 'My Wallet', 'edd-wallet' ) );

			if( edd_get_option( 'edd_wallet_show_value_in_cart', false ) ) {
				$checkout_label .= sprintf( __( ' (%s available)', 'edd-wallet' ), edd_currency_filter( edd_format_amount( $value ) ) );
			}

			// Hide if funds already applied or insufficient
			$fee           = EDD()->fees->get_fee( 'edd-wallet-funds' );
			$allow_partial = edd_get_option( 'edd_wallet_allow_partial', false ) ? true : false;

			if( ( $allow_partial && $value != 0 && ! $fee ) || ( ! $allow_partial && $value >= $total && ! $fee ) ) {
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



	/**
	 * Process payment submission
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $purchase_data The data for a specific purchase
	 * @return      void
	 */
	public function process_payment( $purchase_data ) {
		if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'edd-gateway' ) ) {
			wp_die( __( 'Nonce verification has failed', 'edd-wallet' ), __( 'Error', 'edd-wallet' ), array( 'response' => 403 ) );
		}

		$error = false;

		// Double check that we can afford this item
		$value = edd_wallet()->wallet->balance( $purchase_data['user_email'] );

		if( $value < $purchase_data['price'] ) {
			edd_record_gateway_error( __( 'Wallet Gateway Error', 'edd-wallet' ), __( 'User wallet has insufficient funds.', 'edd-wallet' ), 0 );
			edd_set_error( 'wallet_error', __( 'Insufficient funds.', 'edd-wallet' ) );
			edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
		}

		$payment_data = array(
			'price'         => $purchase_data['price'],
			'date'          => $purchase_data['date'],
			'user_email'    => $purchase_data['user_email'],
			'purchase_key'  => $purchase_data['purchase_key'],
			'currency'      => edd_get_currency(),
			'downloads'     => $purchase_data['downloads'],
			'user_info'     => $purchase_data['user_info'],
			'cart_details'  => $purchase_data['cart_details'],
			'status'        => 'pending'
		);

		// Record the pending payment
		$payment = edd_insert_payment( $payment_data );

		if( $payment ) {
			// Update payment status
			edd_update_payment_status( $payment, 'publish' );

			// Withdraw the funds
			edd_wallet()->wallet->withdraw( $purchase_data['user_info']['id'], $payment_data['price'], 'withdrawal', $payment );

			edd_empty_cart();
			edd_send_to_success_page();
		} else {
			edd_record_gateway_error( __( 'Wallet Gateway Error', 'edd-wallet' ), sprintf( __( 'Payment creation failed while processing a Wallet purchase. Payment data: %s', 'edd-wallet' ), json_encode( $payment_data ) ), $payment );
			edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
		}
	}


	/**
	 * Process refunds
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $payment_id The ID of a payment
	 * @param       string $new_status The new status of the payment
	 * @param       string $old_status The old status of the payment
	 * @return      void
	 */
	public function process_refund( $payment_id, $new_status, $old_status ) {
		if( $old_status != 'publish' && $old_status != 'revoked' ) {
			return;
		}

		if( $new_status != 'refunded' ) {
			return;
		}

		if( edd_get_payment_gateway( $payment_id ) !== 'wallet' ) {
			return;
		}

		$user_id        = edd_get_payment_user_id( $payment_id );
		$refund_amount  = edd_get_payment_amount( $payment_id );

		// Deposit the funds
		edd_wallet()->wallet->deposit( $user_id, $refund_amount, 'refund' );

		// Insert payment note
		edd_insert_payment_note( $payment_id, __( 'Refund completed to Wallet.', 'edd-wallet' ) );
	}
}
new EDD_Wallet_Gateway();
