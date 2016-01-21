<?php
/**
 * Helper functions
 *
 * @package     EDD\Wallet\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get the allowed deposit levels
 *
 * @since       1.0.0
 * @return      array $levels The allowed deposit levels
 */
function edd_wallet_get_deposit_levels() {
	$levels = array();

	$initial_levels = apply_filters( 'edd_wallet_deposit_levels', array(
		'10',
		'20',
		'30',
		'40',
		'50',
		'60',
		'70',
		'80',
		'90',
		'100',
		'200',
		'300',
		'400',
		'500'
	) );

	foreach( $initial_levels as $level ) {
		$levels[$level] = edd_currency_filter( edd_format_amount( $level ) );
	}

	return $levels;
}


/**
 * Get a formatted HTML list of all available email tags
 *
 * Based on edd_get_emails_tags_list()
 *
 * @since       1.0.0
 * @param       string $type Whether this is a standard or admin email
 * @return      $string
 */
function edd_wallet_get_email_tags_list( $type = '' ) {
	// The list
	$list = '';

	// Remove unusable tags
	$unusable_tags = array(
		'download_list',
		'file_urls',
		'subtotal',
		'tax',
		'discount_codes',
	);

	if( $type == 'admin' ) {
		$unusable_tags[] = 'payment_id';
		$unusable_tags[] = 'receipt_id';
		$unusable_tags[] = 'payment_method';
		$unusable_tags[] = 'receipt_link';
		$unusable_tags[] = 'billing_address';
	}

	$unusable_tags = apply_filters( 'edd_wallet_remove_email_tags', $unusable_tags, $type );

	foreach( $unusable_tags as $tag ) {
		edd_wallet_remove_email_tag( $tag );
	}

	// Get all email tags
	$email_tags = edd_get_email_tags();

	// Check
	if ( count( $email_tags ) > 0 ) {

		// Loop
		foreach ( $email_tags as $email_tag ) {

			// Add email tag to list
			$list .= '{' . $email_tag['tag'] . '} - ' . $email_tag['description'] . '<br/>';

		}

	}

	// Return the list
	return $list;
}


/**
 * Our custom email function
 *
 * @since       1.0.0
 * @param       string $type Whether this is a user- or admin-generated email
 * @param       int $id The ID of a payment if user email, or the wallet user if admin email
 * @param       int $item The wallet line item we are sending this for
 * @return      void
 */
function edd_wallet_send_email( $type = 'user', $id = 0, $item = null ) {
	$from_name  = edd_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );

	$from_email = edd_get_option( 'from_email', get_bloginfo( 'admin_email' ) );

	if( $type == 'user' ) {
		$payment_data = edd_get_payment_meta( $id );

		if( ! edd_admin_notices_disabled( $id ) ) {
			edd_wallet_send_admin_email( $id, $payment_data );
		}

		$from_name  = apply_filters( 'edd_purchase_from_name', $from_name, $id, $payment_data );

		$from_email = apply_filters( 'edd_purchase_from_address', $from_email, $id, $payment_data );

		$to_email   = edd_get_payment_user_email( $id );

		$subject    = edd_get_option( 'wallet_receipt_subject', __( 'Receipt for deposit', 'edd-wallet' ) );
		$subject    = apply_filters( 'edd_wallet_receipt_subject', wp_strip_all_tags( $subject ), $id );
		$subject    = edd_wallet_do_email_tags( $subject, $id );

		$message    = edd_get_option( 'wallet_receipt', __( 'Dear', 'edd-wallet' ) . " {name},\n\n" . __( 'Thank you for your deposit. {value} has been added to your wallet.', 'edd-wallet' ) . "\n\n{sitename}" );
		$message    = edd_wallet_do_email_tags( $message, $id );
	} else {

		$user_data  = get_userdata( $id );

		$to_email   = $user_data->user_email;

		if( $type == 'admin-deposit' ) {
			$subject    = edd_get_option( 'wallet_admin_deposit_receipt_subject', __( 'Receipt for deposit', 'edd-wallet' ) );
			$subject    = apply_filters( 'edd_wallet_admin_deposit_subject', wp_strip_all_tags( $subject ), $id );
			$subject    = edd_wallet_do_email_tags( $subject, $item );

			$message    = edd_get_option( 'wallet_admin_deposit_receipt', __( 'Dear', 'edd-wallet' ) . " {name},\n\n" . __( 'The site admin has credited your wallet {value}.', 'edd-wallet' ) . "\n\n{sitename}" );
			$message    = edd_wallet_do_email_tags( $message, $item );
		} else {
			$subject    = edd_get_option( 'wallet_admin_withdrawal_receipt_subject', __( 'Receipt for withdraw', 'edd-wallet' ) );
			$subject    = apply_filters( 'edd_wallet_admin_withdrawal_subject', wp_strip_all_tags( $subject ), $id );
			$subject    = edd_wallet_do_email_tags( $subject, $item );

			$message    = edd_get_option( 'wallet_admin_withdrawal_receipt', __( 'Dear', 'edd-wallet' ) . " {name},\n\n" . __( 'The site admin has deducted {value} from your wallet.', 'edd-wallet' ) . "\n\n{sitename}" );
			$message    = edd_wallet_do_email_tags( $message, $item );
		}
	}

	$emails = EDD()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );

	$emails->send( $to_email, $subject, $message );
}


/**
 * Our custom admin email function
 *
 * @since       1.0.0
 * @param       int $id The ID of a payment if user email, or the wallet user if admin email
 * @param       int $item The wallet line item we are sending this for
 * @return      void
 */
function edd_wallet_send_admin_email( $id = 0, $item = null ) {
	$from_name  = edd_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );

	$from_email = edd_get_option( 'from_email', get_bloginfo( 'admin_email' ) );

	$payment_data = edd_get_payment_meta( $id );

	$from_name  = apply_filters( 'edd_purchase_from_name', $from_name, $id, $payment_data );
	$from_email = apply_filters( 'edd_purchase_from_address', $from_email, $id, $payment_data );

	$subject    = edd_get_option( 'wallet_admin_deposit_notification_subject', __( 'New deposit', 'edd-wallet' ) );
	$subject    = apply_filters( 'edd_wallet_admin_deposit_notification_subject', wp_strip_all_tags( $subject ), $id );
	$subject    = edd_wallet_do_email_tags( $subject, $id );

	$message    = edd_get_option( 'wallet_admin_deposit_notification', __( 'Hello', 'edd-wallet' ) . "\n\n" . __( 'A deposit has been made.', 'edd-wallet' ) . "\n\n" . __( 'Deposited to: {fullname}', 'edd-wallet' ) . "\n" . __( 'Amount: {value}', 'edd-wallet' ) . "\n\n" . __( 'Thank you', 'edd-wallet' ) );
	$message    = edd_wallet_do_email_tags( $message, $id );

	$emails = EDD()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', __( 'New Deposit!', 'edd-wallet' ) );

	$emails->send( edd_get_admin_notice_emails(), $subject, $message );
}