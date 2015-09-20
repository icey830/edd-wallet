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

        // Remove the CC form
        add_action( 'edd_wallet_cc_form', '__return_false' );

        // Process payment
        add_action( 'edd_gateway_wallet', array( $this, 'process_payment' ) );
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
                'id'        => 'edd_wallet_disable_styles',
                'name'      => __( 'Disable Stylesheet', 'edd-wallet' ),
                'desc'      => __( 'Check to disable the deposit form stylesheet and use your own styles', 'edd-wallet' ),
                'type'      => 'checkbox'
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
        $value = get_user_meta( $user_id, '_edd_wallet_value', true );

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
            $value = get_user_meta( $user_id, '_edd_wallet_value', true );

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
        $value = get_user_meta( $purchase_data['user_info']['id'], '_edd_wallet_value', true );

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

        // Subtract the payment from the user wallet
        $value = (float) $value - (float) $payment_data['price'];

        // Record the pending payment
        $payment = edd_insert_payment( $payment_data );

        if( $payment ) {
            // Update payment status
            edd_update_payment_status( $payment, 'publish' );

            // Update wallet
            update_user_meta( $purchase_data['user_info']['id'], '_edd_wallet_value', $value );

            // Record the payment
            $args = array(
                'user_id'       => $purchase_data['user_info']['id'],
                'payment_id'    => $payment,
                'type'          => 'withdrawal',
                'amount'        => (float) $payment_data['price']
            );

            edd_wallet()->wallet->add( $args );

            edd_empty_cart();
            edd_send_to_success_page();
        } else {
            edd_record_gateway_error( __( 'Wallet Gateway Error', 'edd-wallet' ), sprintf( __( 'Payment creation failed while processing a Wallet purchase. Payment data: %s', 'edd-wallet' ), json_encode( $payment_data ) ), $payment );
            edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
        }
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
