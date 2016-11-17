<?php
/**
 * Add custom EDD setting callbacks
 *
 * @package     EDD\Wallet\Admin\Settings\Register
 * @since       1.0.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add settings section
 *
 * @since       1.2.0
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_wallet_settings_section( $sections ) {
	$sections['wallet'] = __( 'Wallet', 'edd-wallet' );

	return $sections;
}
add_filter( 'edd_settings_sections_extensions', 'edd_wallet_settings_section' );


/**
 * Register settings
 *
 * @since       1.2.0
 * @param       array $settings The existing settings
 * @return      array The updated settings
 */
function edd_wallet_register_settings( $settings ) {
	$new_settings = array(
		'wallet' => array(
			array(
				'id'   => 'edd_wallet_general_settings',
				'name' => '<strong>' . __( 'General Settings', 'edd-wallet' ) . '</strong>',
				'desc' => '',
				'type' => 'header'
			),
			array(
				'id'   => 'edd_wallet_cart_label',
				'name' => __( 'Wallet Row Label', 'edd-wallet' ),
				'desc' => __( 'Customize the label for the Wallet cart row', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'My Wallet', 'edd-wallet' )
			),
			array(
				'id'   => 'edd_wallet_cart_funds_label',
				'name' => __( 'Wallet Funds Label', 'edd-wallet' ),
				'desc' => __( 'Customize the label for the Wallet applied funds cart row', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'Wallet Funds', 'edd-wallet' )
			),
			array(
				'id'   => 'edd_wallet_cart_action_label',
				'name' => __( 'Cart Action Label', 'edd-wallet' ),
				'desc' => __( 'Customize the label for the Wallet cart button', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'Apply to purchase', 'edd-wallet' )
			),
			array(
				'id'   => 'edd_wallet_show_value_in_cart',
				'name' => __( 'Display Value', 'edd-wallet' ),
				'desc' => __( 'Display the amount in the users\' wallet next to the cart row label', 'edd-wallet' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'edd_wallet_allow_partial',
				'name' => __( 'Partial Payments', 'edd-wallet' ),
				'desc' => __( 'Allow users to apply wallet funds towards purchase prices', 'edd-wallet' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'edd_wallet_deposit_settings',
				'name' => '<strong>' . __( 'Deposit Settings', 'edd-wallet' ) . '</strong>',
				'desc' => '',
				'type' => 'header'
			),
			array(
				'id'   => 'edd_wallet_deposit_description',
				'name' => __( 'Deposit Description', 'edd-wallet' ),
				'desc' => __( 'Customize how deposits are displayed in cart, enter {val} to display value', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'Deposit to wallet', 'edd-wallet' )
			),
			array(
				'id'   => 'edd_wallet_arbitrary_deposits',
				'name' => __( 'Allow Arbitrary Deposits', 'edd-wallet' ),
				'desc' => __( 'Allow users to enter arbitrary deposit amounts', 'edd-wallet' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'edd_wallet_arbitrary_deposit_label',
				'name' => __( 'Arbitrary Deposit Label', 'edd-wallet' ),
				'desc' => __( 'Customize the text for the arbitrary deposit field label', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'Custom Amount', 'edd-wallet' )
			),
			array(
				'id'   => 'edd_wallet_custom_deposit_error',
				'name' => __( 'Arbitrary Deposit Error', 'edd-wallet' ),
				'desc' => __( 'Customize the text for errors when an arbitrary deposit is missing or invalid', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'You must enter a deposit amount!', 'edd-wallet' )
			),
			array(
				'id'          => 'edd_wallet_deposit_levels',
				'name'        => __( 'Deposit Levels', 'edd-wallet' ),
				'desc'        => __( 'Specify the allowed deposit levels', 'edd-wallet' ),
				'type'        => 'multiselect',
				'chosen'      => true,
				'placeholder' => __( 'Select one or more deposit levels', 'edd-wallet' ),
				'options'     => edd_wallet_get_deposit_levels(),
				'std'         => array(
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
				'id'   => 'edd_wallet_disable_styles',
				'name' => __( 'Disable Stylesheet', 'edd-wallet' ),
				'desc' => __( 'Check to disable the deposit form stylesheet and use your own styles', 'edd-wallet' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'edd_wallet_incentive_settings',
				'name' => '<strong>' . __( 'Incentive Settings', 'edd-wallet' ) . '</strong>',
				'desc' => '',
				'type' => 'header'
			),
			array(
				'id'   => 'edd_wallet_incentive_amount',
				'name' => __( 'Incentive Amount', 'edd-wallet' ),
				'desc' => __( 'Set an optional amount to discount purchases by when paying from a users\' wallet. Example: 10 = 10%', 'edd-wallet' ),
				'type' => 'number',
				'size' => 'small',
				'min'  => 0,
				'step' => .01,
				'std'  => 0
			),
			array(
				'id'      => 'edd_wallet_incentive_type',
				'name'    => __( 'Incentive Type', 'edd-wallet' ),
				'desc'    => __( 'Specify whether incentives are a flat amount, or a percentage.', 'edd-wallet' ),
				'type'    => 'select',
				'options' => array(
					'flatrate' => __( 'Flat Rate', 'edd-wallet' ),
					'percent'  => __( 'Percentage', 'edd-wallet' )
				),
				'std' => 'flatrate'
			),
			array(
				'id'   => 'edd_wallet_incentive_quantities',
				'name' => __( 'Incentive Quantities', 'edd-wallet' ),
				'desc' => __( 'By default, incentives only apply once per item. Check this to include quantities in calculations.', 'edd-wallet' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'edd_wallet_incentive_description',
				'name' => __( 'Incentive Description', 'edd-wallet' ),
				'desc' => __( 'Customize how incentives are displayed in cart.', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'Wallet Discount', 'edd-wallet' )
			),
			'wallet_email_notifications_header' => array(
				'id'   => 'wallet_email_notifications_header',
				'name' => '<strong>' . __( 'Wallet Notifications', 'edd-wallet' ) . '</strong>',
				'desc' => __( 'Configure wallet notification emails', 'edd-wallet' ),
				'type' => 'header'
			),
			'wallet_receipt_subject' => array(
				'id'   => 'wallet_receipt_subject',
				'name' => __( 'Deposit Receipt Subject', 'edd-wallet' ),
				'desc' => __( 'Enter the subject line for user deposit receipts.', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'Receipt for deposit', 'edd-wallet' )
			),
			'wallet_receipt' => array(
				'id'   => 'wallet_receipt',
				'name' => __( 'Deposit Receipt', 'edd-wallet' ),
				'desc' => __( 'Enter the email that is sent to users after completion of a deposit. HTML is accepted. Available template tags:', 'edd-wallet' ) . '<br />' . edd_wallet_get_email_tags_list(),
				'type' => 'rich_editor',
				'std'  => __( 'Dear', 'edd-wallet' ) . " {name},\n\n" . __( 'Thank you for your deposit. {value} has been added to your wallet.', 'edd-wallet' ) . "\n\n{sitename}"
			),
			'wallet_admin_deposit_notification_subject' => array(
				'id'   => 'wallet_admin_deposit_notification_subject',
				'name' => __( 'Admin Deposit Notification Subject', 'edd-wallet' ),
				'desc' => __( 'Enter the subject line for admin notifications when users deposit funds.', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'New deposit', 'edd-wallet' )
			),
			'wallet_admin_deposit_notification' => array(
				'id'   => 'wallet_admin_deposit_notification',
				'name' => __( 'Admin Deposit Notification', 'edd-wallet' ),
				'desc' => __( 'Enter the email that is sent to admins when a users deposit funds. HTML is accepted. Available template tags:', 'edd-wallet' ) . '<br />' . edd_wallet_get_email_tags_list(),
				'type' => 'rich_editor',
				'std'  => __( 'Hello', 'edd-wallet' ) . "\n\n" . __( 'A deposit has been made.', 'edd-wallet' ) . "\n\n" . __( 'Deposited to: {fullname}', 'edd-wallet' ) . "\n" . __( 'Amount: {value}', 'edd-wallet' ) . "\n\n" . __( 'Thank you', 'edd-wallet' )
			),
			'wallet_admin_deposit_receipt_subject' => array(
				'id'   => 'wallet_admin_deposit_subject',
				'name' => __( 'Admin Deposit Subject', 'edd-wallet' ),
				'desc' => __( 'Enter the subject line for admin deposit receipts.', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'Receipt for deposit', 'edd-wallet' )
			),
			'wallet_admin_deposit_receipt' => array(
				'id'   => 'wallet_admin_deposit_receipt',
				'name' => __( 'Admin Deposit Receipt', 'edd-wallet' ),
				'desc' => __( 'Enter the email that is sent to users after completion of a deposit by the admin. HTML is accepted. Available template tags:', 'edd-wallet' ) . '<br />' . edd_wallet_get_email_tags_list( 'admin' ),
				'type' => 'rich_editor',
				'std'  => __( 'Dear', 'edd-wallet' ) . " {name},\n\n" . __( 'The site admin has credited your wallet {value}.', 'edd-wallet' ) . "\n\n{sitename}"
			),
			'wallet_admin_withdrawal_receipt_subject' => array(
				'id'   => 'wallet_admin_withdrawal_subject',
				'name' => __( 'Admin Withdrawal Subject', 'edd-wallet' ),
				'desc' => __( 'Enter the subject line for admin withdrawal receipts.', 'edd-wallet' ),
				'type' => 'text',
				'std'  => __( 'Receipt for withdrawal', 'edd-wallet' )
			),
			'wallet_admin_withdrawal_receipt' => array(
				'id'   => 'wallet_admin_withdrawal_receipt',
				'name' => __( 'Admin Withdrawal Receipt', 'edd-wallet' ),
				'desc' => __( 'Enter the email that is sent to users after completion of a withdraw by the admin. HTML is accepted. Available template tags:', 'edd-wallet' ) . '<br />' . edd_wallet_get_email_tags_list( 'admin' ),
				'type' => 'rich_editor',
				'std'  => __( 'Dear', 'edd-wallet' ) . " {name},\n\n" . __( 'The site admin has deducted {value} from your wallet.', 'edd-wallet' ) . "\n\n{sitename}"
			)
		)
	);

	$settings = array_merge( $settings, $new_settings );

	return $settings;
}
add_filter( 'edd_settings_extensions', 'edd_wallet_register_settings' );



// Only add if the function doesn't exist
if ( ! function_exists( 'edd_multiselect_callback' ) ) {


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
