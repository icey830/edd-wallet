<?php
/**
 * Scripts
 *
 * @package     EDD\Wallet\Scripts
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Load admin scripts
 *
 * @since       1.0.0
 * @param       string $hook The hook for the current page
 * @return      void
 */
function edd_wallet_admin_scripts( $hook ) {
    if( ! apply_filters( 'edd_load_admin_scripts', edd_is_admin_page(), $hook ) ) {
        return;
    }

    // Use minified libraries if SCRIPT_DEBUG is turned off
    $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

    wp_enqueue_style( 'edd-wallet', EDD_WALLET_URL . 'assets/css/admin' . $suffix . '.css', EDD_WALLET_VER );
    wp_enqueue_script( 'edd-wallet', EDD_WALLET_URL . 'assets/js/admin' . $suffix . '.js', EDD_WALLET_VER );
    wp_localize_script( 'edd-wallet', 'edd_wallet_vars', array(
        'type_to_search'    => __( 'Type to search levels', 'edd-wallet' )
    ) );
}
add_action( 'admin_enqueue_scripts', 'edd_wallet_admin_scripts', 100 );


/**
 * Load frontend scripts
 *
 * @since       1.0.0
 * @return      void
 */
function edd_wallet_scripts() {
    // Use minified libraries if SCRIPT_DEBUG is turned off
    $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

    wp_enqueue_style( 'edd-wallet', EDD_WALLET_URL . 'assets/css/edd-wallet' . $suffix . '.css', EDD_WALLET_VER );

    if( edd_get_option( 'edd_wallet_disable_styles', false ) != true ) {
        wp_enqueue_style( 'edd-wallet-deposit', EDD_WALLET_URL . 'assets/css/deposit' . $suffix . '.css', EDD_WALLET_VER );
    }

    $fee = EDD()->fees->get_fee( 'edd-wallet-deposit' );

    if( $fee ) {
        wp_enqueue_script( 'edd-wallet', EDD_WALLET_URL . 'assets/js/edd-wallet' . $suffix . '.js', EDD_WALLET_VER );
    }
}
add_action( 'wp_enqueue_scripts', 'edd_wallet_scripts' );
