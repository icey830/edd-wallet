<?php
/**
 * Add our wallet tab to the customer overview page
 *
 * @package     EDD\Wallet\Admin\Customers\Overview
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Register our custom view
 *
 * @since       1.0.0
 * @param       array $views The current views
 * @return      array $views The updated views
 */
function edd_wallet_customer_view( $views ) {
	$views['wallet'] = 'edd_customer_wallet_view';

	return $views;
}
add_filter( 'edd_customer_views', 'edd_wallet_customer_view' );


/**
 * Register our custom tab
 *
 * @since       1.0.0
 * @param       array $tabs The current tabs
 * @return      array $tabs The updated tabs
 */
function edd_wallet_customer_tabs( $tabs ) {
	$tabs['wallet'] = array(
		'dashicon'  => 'dashicons-money',
		'title'     => __( 'Customer Wallet', 'edd-wallet' )
	);

	return $tabs;
}
add_filter( 'edd_customer_tabs', 'edd_wallet_customer_tabs' );


/**
 * View the wallet of a customer
 *
 * @since       1.0.0
 * @param       object $customer The customer being displayed
 * @return      void
 */
function edd_customer_wallet_view( $customer ) {
	settings_errors( 'edd-notices' );
	?>
	<div id="wallet-header-wrapper" class="customer-section">
		<?php echo get_avatar( $customer->email, 30 ); ?> <span><?php echo sprintf( __( '%s - Wallet', 'edd-wallet' ), $customer->name ); ?></span>
	</div>

	<?php if( $customer->user_id < 1 ) : ?>
		<div class="error"><p><?php _e( 'This customer must be attached to a user account in order to edit their wallet', 'edd-wallet' ); ?></p></div>
	<?php endif; ?>

	<?php do_action( 'edd_customer_wallet_before_stats', $customer ); ?>

	<?php if( $customer->user_id >= 1 ) : ?>

		<?php $value = edd_wallet()->wallet->balance( $customer->user_id ); ?>

		<div id="wallet-stats-wrapper" class="customer-section">
			<ul>
				<li>
					<span class="dashicons dashicons-money"></span>&nbsp;<?php echo sprintf( __( '%s Available', 'edd-wallet' ), edd_currency_filter( edd_format_amount( $value ) ) ); ?>
				</li>
			</ul>
		</div>

		<?php do_action( 'edd_customer_wallet_before_tables_wrapper', $customer ); ?>

		<div id="wallet-tables-wrapper" class="customer-section">

			<?php do_action( 'edd_customer_wallet_before_tables', $customer ); ?>

			<h3><?php _e( 'Recent Activity', 'edd-wallet' ); ?></h3>

			<?php
			$activity = edd_wallet_get_activity( $customer->user_id );
			?>

			<table class="wp-list-table widefat striped activity">
				<thead>
					<tr>
						<th><?php _e( 'ID', 'edd-wallet' ); ?></th>
						<th><?php _e( 'Type', 'edd-wallet' ); ?></th>
						<th><?php _e( 'Amount', 'edd-wallet' ); ?></th>
						<th><?php _e( 'Date', 'edd-wallet' ); ?></th>
						<th><?php _e( 'Status', 'edd-wallet' ); ?></th>
						<th><?php _e( 'Actions', 'edd-wallet' ); ?></th>
					</tr>
				</thead>
				<tbody>

				<?php
				if( $activity ) {
					foreach( $activity as $item ) {
						// Setup item type
						switch( $item->type ) {
							case 'deposit':
								$type = __( 'Deposit', 'edd-wallet' );
								$item_id = $item->id . ' (' . $item->payment_id . ')';
								$actions = '<a title="' . __( 'View Details for Payment', 'edd-wallet' ) . ' ' . $item->payment_id . '" href="' . admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $item->payment_id ) . '">' . __( 'View Details', 'edd-wallet' ) . '</a>';
								$status = edd_get_payment_status( get_post( $item->payment_id ), true );
								break;
							case 'withdrawal':
								$type = __( 'Withdraw', 'edd-wallet' );
								$item_id = $item->id . ' (' . $item->payment_id . ')';
								$actions = '<a title="' . __( 'View Details for Payment', 'edd-wallet' ) . ' ' . $item->payment_id . '" href="' . admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $item->payment_id ) . '">' . __( 'View Details', 'edd-wallet' ) . '</a>';
								$status = edd_get_payment_status( get_post( $item->payment_id ), true );
								break;
							case 'admin-deposit':
								$type = __( 'Admin Deposit', 'edd-wallet' );
								$item_id = $item->id;
								$actions = '';
								$status = __( 'Complete', 'edd-wallet' );
								break;
							case 'admin-withdraw':
								$type = __( 'Admin Withdraw', 'edd-wallet' );
								$item_id = $item->id;
								$actions = '';
								$status = __( 'Complete', 'edd-wallet' );
								break;
							case 'refund':
								$type = __( 'Refund', 'edd-wallet' );
								$item_id = $item->id;
								$actions = '';
								$status = __( 'Complete', 'edd-wallet' );
								break;
							default:
								$type = apply_filters( 'edd_wallet_activity_type', $item->type, $item );
								$item_id = apply_filters( 'edd_wallet_activity_item_id', $item->id, $item );
								$actions = apply_filters( 'edd_wallet_activity_actions', '', $item );
								$status = apply_filters( 'edd_wallet_activity_status', __( 'Complete', 'edd-wallet' ), $item );
								break;
						}
						?>
						<tr>
							<td><?php echo $item_id; ?></td>
							<td><?php echo $type; ?></td>
							<td><?php echo edd_currency_filter( edd_format_amount( $item->amount ) ); ?></td>
							<td><?php echo $item->date_created; ?></td>
							<td><?php echo $status; ?></td>
							<td>
								<?php echo $actions; ?>
								<?php do_action( 'edd_wallet_recent_history_actions', $customer, $item ); ?>
							</td>
						</tr>
						<?php
					}
				} else {
					echo '<tr><td colspan="6">' . __( 'No Activity Found', 'edd-wallet' ) . '</td></tr>';
				}
				?>

				</tbody>
			</table>

			<div class="edd-wallet-edit-wallet">
				<a class="button-secondary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'edd-wallet-edit', 'id' => $customer->id, 'edd-message' => false ), admin_url( 'options.php' ) ) ); ?>"><?php _e( 'Edit Wallet', 'edd-wallet' ); ?></a>
			</div>

		<?php endif; ?>

		<?php do_action( 'edd_customer_wallet_after_tables', $customer ); ?>

	</div>
	<?php
	do_action( 'edd_customer_wallet_card_bottom', $customer );
}
