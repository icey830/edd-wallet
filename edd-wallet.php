<?php
/**
 * Plugin Name:     Easy Digital Downloads - Wallet
 * Plugin URI:      https://easydigitaldownloads.com/extension/wallet
 * Description:     Add a store credit system to Easy Digital Downloads
 * Version:         1.1.4
 * Author:          Easy Digital Downloads
 * Author URI:      https://easydigitaldownloads.com
 * Text Domain:     edd-wallet
 *
 * @package         EDD\Wallet
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Plugin version needs to be accessible to both the
// installer and the main class
define( 'EDD_WALLET_VER', '1.1.4' );


if( ! class_exists( 'EDD_Wallet' ) ) {


	/**
	 * Main EDD_Wallet class
	 *
	 * @since       1.0.0
	 */
	class EDD_Wallet {


		/**
		 * @var         EDD_Wallet $instance The one true EDD_Wallet
		 * @since       1.0.0
		 */
		private static $instance;


		/**
		 * @var         object $db EDD Wallet DB object
		 * @since       1.0.0
		 */
		public $db;


		/**
		 * @var         object $email_tags EDD Wallet Email Tags object
		 * @since       1.0.0
		 */
		public $email_tags;


		/**
		 * @var			object $wallet EDD Wallet Helper object
		 * @since		1.0.1
		 */
		public $wallet;


		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      self::$instance The one true EDD_Wallet
		 */
		public static function instance() {
			if( ! self::$instance && function_exists( 'EDD' ) ) {
				self::$instance = new EDD_Wallet();
				self::$instance->setup_constants();
				self::$instance->load_textdomain();
				self::$instance->includes();
				self::$instance->hooks();
				self::$instance->db = new EDD_DB_Wallet();
				self::$instance->email_tags = new EDD_Wallet_Email_Template_Tags();
				self::$instance->wallet = new EDD_Wallet_Helper();
			}

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function setup_constants() {
			// Plugin path
			define( 'EDD_WALLET_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin URL
			define( 'EDD_WALLET_URL', plugin_dir_url( __FILE__ ) );
		}


		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function includes() {

			require_once EDD_WALLET_DIR . 'includes/scripts.php';
			require_once EDD_WALLET_DIR . 'includes/functions.php';
			require_once EDD_WALLET_DIR . 'includes/shortcodes.php';
			require_once EDD_WALLET_DIR . 'includes/widgets.php';
			require_once EDD_WALLET_DIR . 'includes/deposit-functions.php';
			require_once EDD_WALLET_DIR . 'includes/class.edd-wallet-gateway.php';
			require_once EDD_WALLET_DIR . 'includes/class.edd-wallet-helper.php';
			require_once EDD_WALLET_DIR . 'includes/class.edd-db-wallet.php';
			require_once EDD_WALLET_DIR . 'includes/class.edd-email-tags.php';
			require_once EDD_WALLET_DIR . 'includes/ajax-functions.php';
			require_once EDD_WALLET_DIR . 'includes/incentive-functions.php';

			if( is_admin() ) {
				require_once EDD_WALLET_DIR . 'includes/admin/pages.php';
				require_once EDD_WALLET_DIR . 'includes/admin/settings/register.php';
				require_once EDD_WALLET_DIR . 'includes/admin/customers/customers.php';
				require_once EDD_WALLET_DIR . 'includes/admin/customers/customer-table.php';
			}
		}


		/**
		 * Run action and filter hooks
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function hooks() {
			// Handle licensing
			if( class_exists( 'EDD_License' ) ) {
				$license = new EDD_License( __FILE__, 'Wallet', EDD_WALLET_VER, 'Daniel J Griffiths' );
			}

			// Add email settings
			add_filter( 'edd_settings_emails', array( $this, 'email_settings' ) );

			// Add style settings
			add_filter( 'edd_settings_styles', array( $this, 'style_settings' ) );
		}


		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function load_textdomain() {
			// Set filter for language directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'edd_wallet_language_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), '' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'edd-wallet', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/edd-wallet/' . $mofile;

			if( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-wallet/ folder
				load_textdomain( 'edd-wallet', $mofile_global );
			} elseif( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-wallet/languages/ folder
				load_textdomain( 'edd-wallet', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'edd-wallet', false, $lang_dir );
			}
		}


		/**
		 * Register email settings
		 *
		 * @access      public
		 * @since       1.0.0
		 * @param       array $settings The existing settings
		 * @return      array $settings The updated settings
		 */
		public function email_settings( $settings ) {
			$new_settings = array(
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
			);

			return array_merge( $settings, $new_settings );
		}


		/**
		 * Register style settings
		 *
		 * @access      public
		 * @since       1.0.0
		 * @param       array $settings The existing settings
		 * @return      array $settings The updated settings
		 */
		public function style_settings( $settings ) {
			$new_settings = array(
				'wallet_style_header' => array(
					'id'    => 'wallet_style_header',
					'name'  => '<strong>' . __( 'Wallet Styles', 'edd-wallet' ) . '</strong>',
					'desc'  => __( 'Configure wallet styles', 'edd-wallet' ),
					'type'  => 'header'
				),
				array(
					'id'    => 'edd_wallet_disable_styles',
					'name'  => __( 'Disable Stylesheet', 'edd-wallet' ),
					'desc'  => __( 'Check to disable the deposit form stylesheet and use your own styles', 'edd-wallet' ),
					'type'  => 'checkbox'
				)
			);

			return array_merge( $settings, $new_settings );
		}
	}
}


/**
 * The main function responsible for returning the one true EDD_Wallet
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      EDD_Wallet The one true EDD_Wallet
 */
function edd_wallet() {
	if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		if( ! class_exists( 'S214_EDD_Activation' ) ) {
			require_once 'includes/libraries/class.s214-edd-activation.php';
		}

		$activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();

		return EDD_Wallet::instance();
	} else {
		return EDD_Wallet::instance();
	}
}
add_action( 'plugins_loaded', 'edd_wallet' );


/**
 * Install
 *
 * @since       1.0.0
 * @global      object $wpdb The WordPress database object
 * @return      void
 */
function edd_wallet_install() {
	global $wpdb;

	// Add upgraded from option
	$current_version = get_option( 'edd_wallet_version' );
	if( $current_version ) {
		update_option( 'edd_wallet_version_upgraded_from', $current_version );
	}

	update_option( 'edd_wallet_version', EDD_WALLET_VER );

	// Create the wallet database table
	require_once 'includes/class.edd-db-wallet.php';
	$wallet = new EDD_DB_Wallet();

	if( ! $wallet->installed() ) {
		$wallet->create_table();
	}
}
register_activation_hook( __FILE__, 'edd_wallet_install' );
