<?php
/**
 * Add custom EDD setting callbacks
 *
 * @package     EDD\Wallet\Admin\Settings\Register
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add email settings section
 *
 * @since       2.0.0
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_wallet_add_email_settings_section( $sections ) {
	$sections['wallet'] = __( 'Wallet Notifications', 'edd-wallet' );

	return $sections;
}
add_filter( 'edd_settings_sections_emails', 'edd_wallet_add_email_settings_section' );


/**
 * Register email settings
 *
 * @since       2.0.0
 * @param       array $settings The existing settings
 * @return      array $settings The updated settings
 */
function edd_wallet_add_email_settings( $settings ) {
	$new_settings = array(
		'wallet' => apply_filters( 'edd_wallet_email_settings', array(
			'wallet_email_notifications_header' => array(
				'id'    => 'wallet_email_notifications_header',
				'name'  => '<strong>' . __( 'Wallet Notifications', 'edd-wallet' ) . '</strong>',
				'desc'  => __( 'Configure wallet notification emails', 'edd-wallet' ),
				'type'  => 'header'
			),
			'wallet_receipt_subject' => array(
				'id'    => 'wallet_receipt_subject',
				'name'  => __( 'Deposit Receipt Subject', 'edd-wallet' ),
				'desc'  => __( 'Enter the subject line for user deposit receipts.', 'edd-wallet' ),
				'type'  => 'text',
				'std'   => __( 'Receipt for deposit', 'edd-wallet' )
			),
			'wallet_receipt' => array(
				'id'    => 'wallet_receipt',
				'name'  => __( 'Deposit Receipt', 'edd-wallet' ),
				'desc'  => __( 'Enter the email that is sent to users after completion of a deposit. HTML is accepted. Available template tags:', 'edd-wallet' ) . '<br />' . edd_wallet_get_email_tags_list(),
				'type'  => 'rich_editor',
				'std'   => __( 'Dear', 'edd-wallet' ) . " {name},\n\n" . __( 'Thank you for your deposit. {value} has been added to your wallet.', 'edd-wallet' ) . "\n\n{sitename}"
			),
			'wallet_admin_deposit_notification_subject' => array(
				'id'    => 'wallet_admin_deposit_notification_subject',
				'name'  => __( 'Admin Deposit Notification Subject', 'edd-wallet' ),
				'desc'  => __( 'Enter the subject line for admin notifications when users deposit funds.', 'edd-wallet' ),
				'type'  => 'text',
				'std'   => __( 'New deposit', 'edd-wallet' )
			),
			'wallet_admin_deposit_notification' => array(
				'id'    => 'wallet_admin_deposit_notification',
				'name'  => __( 'Admin Deposit Notification', 'edd-wallet' ),
				'desc'  => __( 'Enter the email that is sent to admins when a users deposit funds. HTML is accepted. Available template tags:', 'edd-wallet' ) . '<br />' . edd_wallet_get_email_tags_list(),
				'type'  => 'rich_editor',
				'std'   => __( 'Hello', 'edd-wallet' ) . "\n\n" . __( 'A deposit has been made.', 'edd-wallet' ) . "\n\n" . __( 'Deposited to: {fullname}', 'edd-wallet' ) . "\n" . __( 'Amount: {value}', 'edd-wallet' ) . "\n\n" . __( 'Thank you', 'edd-wallet' )
			),
			'wallet_admin_deposit_receipt_subject' => array(
				'id'    => 'wallet_admin_deposit_subject',
				'name'  => __( 'Admin Deposit Subject', 'edd-wallet' ),
				'desc'  => __( 'Enter the subject line for admin deposit receipts.', 'edd-wallet' ),
				'type'  => 'text',
				'std'   => __( 'Receipt for deposit', 'edd-wallet' )
			),
			'wallet_admin_deposit_receipt' => array(
				'id'    => 'wallet_admin_deposit_receipt',
				'name'  => __( 'Admin Deposit Receipt', 'edd-wallet' ),
				'desc'  => __( 'Enter the email that is sent to users after completion of a deposit by the admin. HTML is accepted. Available template tags:', 'edd-wallet' ) . '<br />' . edd_wallet_get_email_tags_list( 'admin' ),
				'type'  => 'rich_editor',
				'std'   => __( 'Dear', 'edd-wallet' ) . " {name},\n\n" . __( 'The site admin has credited your wallet {value}.', 'edd-wallet' ) . "\n\n{sitename}"
			),
			'wallet_admin_withdrawal_receipt_subject' => array(
				'id'    => 'wallet_admin_withdrawal_subject',
				'name'  => __( 'Admin Withdrawal Subject', 'edd-wallet' ),
				'desc'  => __( 'Enter the subject line for admin withdrawal receipts.', 'edd-wallet' ),
				'type'  => 'text',
				'std'   => __( 'Receipt for withdrawal', 'edd-wallet' )
			),
			'wallet_admin_withdrawal_receipt' => array(
				'id'    => 'wallet_admin_withdrawal_receipt',
				'name'  => __( 'Admin Withdrawal Receipt', 'edd-wallet' ),
				'desc'  => __( 'Enter the email that is sent to users after completion of a withdraw by the admin. HTML is accepted. Available template tags:', 'edd-wallet' ) . '<br />' . edd_wallet_get_email_tags_list( 'admin' ),
				'type'  => 'rich_editor',
				'std'   => __( 'Dear', 'edd-wallet' ) . " {name},\n\n" . __( 'The site admin has deducted {value} from your wallet.', 'edd-wallet' ) . "\n\n{sitename}"
			)
		) )
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'edd_settings_emails', 'edd_wallet_add_email_settings' );


// Only add if the function doesn't exist
if( ! function_exists( 'edd_multiselect_callback' ) ) {


	/**
	 * Multiselect Callback
	 *
	 * The EDD select callback hasn't been updated to use
	 * the HTML_Elements class, so doesn't support multiselect yet...
	 *
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array $edd_options Array of all the EDD Options
	 * @return void
	 */
	function edd_multiselect_callback($args) {
		global $edd_options;

		if ( isset( $edd_options[ $args['id'] ] ) ) {
			$value = $edd_options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( isset( $args['placeholder'] ) ) {
			$placeholder = $args['placeholder'];
		} else {
			$placeholder = '';
		}

		if ( isset( $args['chosen'] ) ) {
			$chosen = 'class="edd-wallet-select-chosen"';
		} else {
			$chosen = '';
		}

		$html = '<select id="edd_settings[' . $args['id'] . ']" name="edd_settings[' . $args['id'] . '][]" ' . $chosen . 'data-placeholder="' . $placeholder . '" multiple />';

		if( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $option => $name ) {
				if( is_array( $value ) ) {
					$selected = selected( true, in_array( $option, $value ), false );
				} else {
					$selected = selected( $value, $option, false );
				}

				$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
			}
		}

		$html .= '</select>';
		$html .= '<label for="edd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}
}
