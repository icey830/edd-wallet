<?php
/**
 * Upgrades
 *
 * @package     EDD\Wallet\Upgrades
 * @since       1.2.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Run upgrades
 *
 * @since       1.2.0
 * @return      void
 */
function edd_wallet_run_upgrades() {
	global $edd_options;

	if ( ! get_option( 'edd_wallet_upgrade_120' ) ) {
		// Upgrade gateway settings
		if ( isset( $edd_options['edd_wallet_gateway_label'] ) && ( ! empty( $edd_options['edd_wallet_gateway_label'] ) || $edd_options['edd_wallet_gateway_label'] != '' ) ) {
			$edd_options['edd_wallet_cart_label'] = $edd_options['edd_wallet_gateway_label'];
		}
		if ( isset( $edd_options['edd_wallet_gateway_label_value'] ) && ( ! empty( $edd_options['edd_wallet_gateway_label_value'] ) || $edd_options['edd_wallet_gateway_label_value'] != '' ) ) {
			$edd_options['edd_wallet_show_value_in_cart'] = '1';
		}

		update_option( 'edd_settings', $edd_options );
		update_option( 'edd_wallet_upgrade_120', '1' );
	}
}
