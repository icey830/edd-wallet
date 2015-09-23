<?php
/**
 * AJAX functions
 *
 * @package		EDD\Wallet\AJAX
 * @since		1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


function edd_wallet_process_incentive() {
	echo 'test';
	edd_die();
}
add_action( 'wp_ajax_edd_wallet_process_incentive', 'edd_wallet_process_incentive' );
add_action( 'wp_ajax_nopriv_edd_wallet_process_incentive', 'edd_wallet_process_incentive' );