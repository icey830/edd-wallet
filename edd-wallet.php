<?php
/**
 * Plugin Name:     Easy Digital Downloads - Wallet
 * Plugin URI:      https://easydigitaldownloads.com/extension/wallet
 * Description:     Add a store credit system to Easy Digital Downloads
 * Version:         1.1.3
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
define( 'EDD_WALLET_VER', '1.1.3' );


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
