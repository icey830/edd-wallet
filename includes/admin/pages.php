<?php
/**
 * Admin pages
 *
 * @package     EDD\Wallet\Admin\Pages
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Create admin pages
 *
 * @since       1.0.0
 * @return      void
 */
function edd_wallet_admin_pages() {
	add_submenu_page( 'options.php', __( 'Edit Wallet', 'edd-wallet' ), __( 'Edit Wallet', 'edd-wallet' ), 'edit_shop_payments', 'edd-wallet-edit', 'edd_wallet_edit_form' );
}
add_action( 'admin_menu', 'edd_wallet_admin_pages' );


/**
 * Display edit wallet page
 *
 * @since       1.0.0
 * @return      void
 */
function edd_wallet_edit_form() {
	$return_url = esc_url( add_query_arg( array( 'post_type' => 'download', 'page' => 'edd-customers', 'view' => 'wallet', 'id' => $_GET['id'] ), admin_url( 'edit.php' ) ) );
	$customer   = new EDD_Customer( $_GET['id'] );
	?>
	<div class="wrap">
		<h2><?php _e( 'Edit Wallet', 'edd-wallet' ); ?>&nbsp;<a href="<?php echo $return_url; ?>" class="page-title-action"><?php _e( 'Back to Details', 'edd-wallet' ); ?></a></h2>

		<form id="edd_wallet_admin_deposit" method="post">
			<table class="form-table">
				<tbody id="edd-wallet-table-body">
					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="wallet-edit-type"><?php _e( 'Edit Type', 'edd-wallet' ); ?></label>
						</th>
						<td>
							<select name="wallet-edit-type">
								<option value="admin-deposit"><?php _e( 'Deposit', 'edd-wallet' ); ?></option>
								<option value="admin-withdraw"><?php _e( 'Withdraw', 'edd-wallet' ); ?></option>
							</select>
							<div class="descirption"><?php _e( 'Withdrawing funds simply removes them from the wallet and should be used with caution!', 'edd-wallet' ); ?></div>
						</td>
					</tr>
					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="wallet-amount"><?php _e( 'Amount', 'edd-wallet' ); ?></label>
						</th>
						<td>
							<input type="text" class="small-text" id="wallet-amount" name="wallet-amount" style="width: 180px" />
							<div class="description"><?php _e( 'Enter the amount to deposit or withdraw.', 'edd-wallet' ); ?></div>
						</td>
					</tr>
					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="wallet-receipt"><?php _e( 'Send Receipt', 'edd-wallet' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="wallet-receipt" name="wallet-receipt" checked="1" value="1" />
							<?php _e( 'Send the deposit receipt to the customer?', 'edd-wallet' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php wp_nonce_field( 'edd-wallet-admin-deposit-nonce' ); ?>
			<input type="hidden" name="wallet-user" value="<?php echo $customer->user_id; ?>" />
			<input type="hidden" name="edd_action" value="wallet_process_admin_deposit" />
			<?php submit_button( __( 'Edit Wallet', 'edd-wallet' ) ); ?>
		</form>
	</div>
	<?php
}
