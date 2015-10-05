<?php
/**
 * Wallet helper class
 *
 * @package     EDD\Wallet\Helper
 * @since       1.0.1
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main EDD_Wallet_Helper class
 *
 * @since       1.0.1
 */
class EDD_Wallet_Helper {


	/**
	 * Get things started
	 *
	 * @access      public
	 * @since       1.0.1
	 * @return      void
	 */
	public function __construct() {}


	/**
	 * Deposit funds to wallet
	 *
	 * @access		public
	 * @since		1.0.1
	 * @param		mixed $user The user ID or email
	 * @param		float $amount The amount to deposit
	 * @param		string $type The type of deposit
	 * @param		int $payment_id The ID of a given payment
	 * @return		mixed
	 */
	public function deposit( $user, $amount, $type = 'deposit', $payment_id = 0 ) {

		if( is_email( $user ) || strpos( $user, '@' ) !== false ) {
			$user = get_user_by( 'email', $user );
			$user = $user->ID;
		}

		$value  = $this->balance( $user );
		$value += $amount;

		// Update the user wallet
		update_user_meta( $user, '_edd_wallet_value', $value );

		// Record the deposit
		$args = array(
			'user_id'       => $user,
			'payment_id'    => $payment_id,
			'type'          => $type,
			'amount'        => $amount
		);

		do_action( 'edd_wallet_deposit', $args );

		return edd_wallet()->db->add( $args );
	}


	/**
	 * Withdraw funds from wallet
	 *
	 * @access		public
	 * @since		1.0.1
	 * @param		mixed $user The user ID or email
	 * @param		float $amount The amount to withdraw
	 * @param		string $type The type of deposit
	 * @param		int $payment_id The ID of a given payment
	 * @return		mixed
	 */
	public function withdraw( $user, $amount, $type = 'withdrawal', $payment_id = 0 ) {
		if( is_email( $user ) || strpos( $user, '@' ) !== false ) {
			$user = get_user_by( 'email', $user );
			$user = $user->ID;
		}

		$value  = $this->balance( $user );
		$value -= $amount;

		// Update the user wallet
		update_user_meta( $user, '_edd_wallet_value', $value );

		// Record the deposit
		$args = array(
			'user_id'       => $user,
			'payment_id'    => $payment_id,
			'type'          => $type,
			'amount'        => $amount
		);

		$item = edd_wallet()->db->add( $args );

		// Override customer value increase
		$customer = new EDD_Customer( $user );
		$customer->decrease_value( $amount );

		do_action( 'edd_wallet_withdraw', $args );

		return $item;

	}


	/**
	 * Check the balance of a users' wallet
	 *
	 * @access		public
	 * @since		1.0.1
	 * @param		mixed $user The user ID or email
	 * @return		float $balance The users' balance
	 */
	public function balance( $user ) {
		if( is_email( $user ) || strpos( $user, '@' ) !== false ) {
			$user = get_user_by( 'email', $user );
			$user = $user->ID;
		}

		$value = get_user_meta( $user, '_edd_wallet_value', true );

		return (float) $value;
	}
}