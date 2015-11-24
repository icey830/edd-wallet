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
		// Register settings
		add_filter( 'edd_settings_gateways', array( $this, 'settings' ) );

		// Add the gateway
		add_filter( 'edd_payment_gateways', array( $this, 'register_gateway' ) );

		// Maybe show the gateway
		add_filter( 'edd_enabled_payment_gateways', array( $this, 'show_gateway' ) );

		// Override chosen gateway
		add_filter( 'edd_chosen_gateway', array( $this, 'chosen_gateway' ) );

		// Remove the CC form
		add_action( 'edd_wallet_cc_form', '__return_false' );

		// Process payment
		add_action( 'edd_gateway_wallet', array( $this, 'process_payment' ) );

		// Process refunds
		add_action( 'edd_update_payment_status', array( $this, 'process_refund' ), 200, 3 );
	}


	/**
	 * Settings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $settings The existing settings
	 * @return      array The updated settings
	 */
	public function settings( $settings ) {
		$new_settings = array(
			array(
				'id'        => 'edd_wallet_gateway_settings',
				'name'      => '<strong>' . __( 'Wallet Settings', 'edd-wallet' ) . '</strong>',
				'desc'      => '',
				'type'      => 'header'
			),
			array(
				'id'        => 'edd_wallet_gateway_label',
				'name'      => __( 'Gateway Label', 'edd-wallet' ),
				'desc'      => __( 'Customize the gateway label', 'edd-wallet' ),
				'type'      => 'text',
				'std'       => __( 'My Wallet', 'edd-wallet' )
			),
			array(
				'id'        => 'edd_wallet_gateway_label_value',
				'name'      => __( 'Display Value', 'edd-wallet' ),
				'desc'      => __( 'Display the amount in the users\' wallet next to the gateway label', 'edd-wallet' ),
				'type'      => 'checkbox'
			),
			array(
				'id'        => 'edd_wallet_deposit_description',
				'name'      => __( 'Deposit Description', 'edd-wallet' ),
				'desc'      => __( 'Customize how deposits are displayed in cart, enter {val} to display value', 'edd-wallet' ),
				'type'      => 'text',
				'std'       => __( 'Deposit to wallet', 'edd-wallet' )
			),
			array(
				'id'        => 'edd_wallet_deposit_levels',
				'name'      => __( 'Deposit Levels', 'edd-wallet' ),
				'desc'      => __( 'Specify the allowed deposit levels', 'edd-wallet' ),
				'type'      => 'multiselect',
				'chosen'    => true,
				'placeholder'   => __( 'Select one or more deposit levels', 'edd-wallet' ),
				'options'   => edd_wallet_get_deposit_levels(),
				'std'       => array(
					'20',
					'40',
					'60',
					'80',
					'100',
					'200',
					'500'
				)
			),
			array(
				'id'        => 'edd_wallet_incentive_amount',
				'name'      => __( 'Incentive Amount', 'edd-wallet' ),
				'desc'      => __( 'Set an optional amount to discount purchases by when paying from a users\' wallet. Example: 10 = 10%', 'edd-wallet' ),
				'type'      => 'number',
				'size'      => 'small',
				'min'       => 0,
				'step'      => .01,
				'std'       => 0
			),
			array(
				'id'        => 'edd_wallet_incentive_type',
				'name'      => __( 'Incentive Type', 'edd-wallet' ),
				'desc'      => __( 'Specify whether incentives are a flat amount, or a percentage.', 'edd-wallet' ),
				'type'      => 'select',
				'options'   => array(
					'flatrate'  => __( 'Flat Rate', 'edd-wallet' ),
					'percent'   => __( 'Percentage', 'edd-wallet' )
				),
				'std'       => 'flatrate'
			),
			array(
				'id'        => 'edd_wallet_incentive_quantities',
				'name'      => __( 'Incentive Quantities', 'edd-wallet' ),
				'desc'      => __( 'By default, incentives only apply once per item. Check this to include quantities in calculations.', 'edd-wallet' ),
				'type'      => 'checkbox'
			),
			array(
				'id'        => 'edd_wallet_incentive_description',
				'name'      => __( 'Incentive Description', 'edd-wallet' ),
				'desc'      => __( 'Customize how incentives are displayed in cart.', 'edd-wallet' ),
				'type'      => 'text',
				'std'       => __( 'Wallet Discount', 'edd-wallet' )
			)
		);

		return array_merge( $settings, $new_settings );
	}


	/**
	 * Register our new gateway
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $gateways The current gateway list
	 * @return      array $gateways The updated gateway list
	 */
	public function register_gateway( $gateways ) {
		$user_id = get_current_user_id();
		$value = edd_wallet()->wallet->balance( $user_id );

		$checkout_label = edd_get_option( 'edd_wallet_gateway_label', __( 'My Wallet', 'edd-wallet' ) );

		if( edd_get_option( 'edd_wallet_gateway_label_value', false ) == true ) {
			$checkout_label .= ' ' . sprintf( __( '(%s available)', 'edd-wallet' ), edd_currency_filter( edd_format_amount( $value ) ) );
		}

		$gateways['wallet'] = array(
			'admin_label'       => 'Wallet',
			'checkout_label'    => $checkout_label
		);

		return $gateways;
	}


	/**
	 * Maybe show the gateway
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $gateways The enabled gateways
	 * @return      array $gateways The updated gateways
	 */
	public function show_gateway( $gateways ) {
		if( is_user_logged_in() ) {

			// Get the current user
			$user_id = get_current_user_id();

			// Get the wallet value
			$value = edd_wallet()->wallet->balance( $user_id );

			// Get the cart total
			$total = edd_get_cart_total();

			// Make sure we aren't making a deposit from our wallet
			$fee = EDD()->fees->get_fee( 'edd-wallet-deposit' );

			if( (float) $value < (float) $total || $fee ) {
				unset( $gateways['wallet'] );
			}
		}

		return $gateways;
	}


	/**
	 * Fix chosen gateway
	 *
	 * @since       1.0.7
	 * @param       array $gateway The current chosen gateway
	 * @return      array $gateway The fixed chosen gateway
	 */
	public function chosen_gateway( $gateway ) {
		if( is_user_logged_in() ) {

			// Get the current user
			$user_id = get_current_user_id();

			// Make sure we aren't making a deposit from our wallet
			$fee = EDD()->fees->get_fee( 'edd-wallet-deposit' );

			if( $fee ) {
				$gateways = edd_get_enabled_payment_gateways();

				if( count( $gateways ) == 1 ) {
					$gateway = array_keys( $gateways );
					$gateway = $gateway[0];
				}
			}
		}

		return $gateway;
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


	/**
	 * Output form errors
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function errors_div() {
		echo '<div id="edd-wallet-errors"></div>';
	}
}
new EDD_Wallet_Gateway();
