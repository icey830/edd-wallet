<?php
/**
 * Wallet DB class
 *
 * @package     EDD\Wallet\DB
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * EDD_DB_Wallet class
 *
 * @since       1.0.0
 */
class EDD_DB_Wallet extends EDD_DB {


	/**
	 * Get things started
	 *
	 * @access      public
	 * @since       1.0.0
	 * @global      object $wpdb The WordPress database object
	 * @return      void
	 */
	public function __construct() {
	    global $wpdb;

		$this->table_name   = $wpdb->prefix . 'edd_wallet';
		$this->primary_key  = 'id';
		$this->version      = '1.0.0';
	}


	/**
	 * Get columns and formats
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      array The columns and formats
	 */
	public function get_columns() {
		return array(
			'id'            => '%d',
			'user_id'       => '%d',
			'payment_id'    => '%s',
			'type'          => '%s',
			'amount'        => '%f',
			'date_created'  => '%s'
		);
	}


	/**
	 * Get default column values
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      array The default values
	 */
	public function get_column_defaults() {
		return array(
			'user_id'       => 0,
			'payment_id'    => 0,
			'type'          => '',
			'amount'        => 0.00,
			'date_created'  => date( 'Y-m-d H:i:s' )
		);
	}


	/**
	 * Add a line item
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function add( $data = array() ) {
		$defaults = array(
			'payment_id' => ''
		);

		$args = wp_parse_args( $data, $defaults );

		if( empty( $args['user_id'] ) ) {
			return false;
		}

		return $this->insert( $args, 'wallet_item' );
	}


	/**
	 * Get the wallet data for a given customer
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $user_id The user ID to search
	 * @param       string $type The activity type to search
	 * @global      object $wpdb The WordPress database object
	 * @return      mixed
	 */
	public function get_customer_wallet( $user_id = 0, $type = '' ) {
		global $wpdb;

		if( $type ) {
			$type = "AND `type`='{$type}' ";
		}

		$wallet = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE 1=1 AND `user_id` IN( {$user_id} ) ${type}ORDER BY id DESC LIMIT %d,%d;", 0, 20 ) );

		return $wallet;
	}


	/**
	 * Get a specific wallet item
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $id The customer ID to search
	 * @return      mixed
	 */
	public function get_customer_wallet_item( $id = 0 ) {
		global $wpdb;

		if( ! is_numeric( $id ) ) {
			return false;
		}

		$id = intval( $id );

		if( $id < 1 ) {
			return false;
		}

		if( ! $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %s LIMIT 1", $id ) ) ) {
			return false;
		}

		return $item;
	}


	/**
	 * Create the table
	 *
	 * @access      public
	 * @since       1.0.0
	 * @global      object $wpdb The WordPress database object
	 * @return      void
	 */
	public function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE " . $this->table_name . " (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			payment_id bigint(20) NOT NULL,
			type mediumtext NOT NULL,
			amount mediumtext NOT NULL,
			date_created datetime NOT NULL,
			PRIMARY KEY (id),
			KEY user (user_id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}


	/**
	 * Check if the Wallet table was installed
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      bool
	 */
	public function installed() {
		return $this->table_exists( $this->table_name );
	}
}
