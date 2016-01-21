<?php
/**
 * Widgets
 *
 * @package     EDD\Wallet\Widgets
 * @since       1.1.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Wallet widget
 *
 * @since       1.1.0
 * @return      void
 */
class edd_wallet_widget extends WP_Widget {


	/**
	 * Get things started
	 *
	 * @access      public
	 * @since       1.1.0
	 * @return      void
	 */
	public function __construct() {
		parent::__construct( 'edd_wallet_widget', __( 'Wallet', 'edd-wallet' ), array( 'description' => __( 'Display the wallet value for the current user', 'edd-wallet' ) ) );
	}


	/**
	 * Display widget
	 *
	 * @access      public
	 * @since       1.1.0
	 * @param       array $args Arguements for the widget
	 * @param       array $instance This widget instance
	 * @return      void
	 */
	public function widget( $args, $instance ) {
		// Bail if user isn't logged in
		if( ! is_user_logged_in() ) {
			return;
		}

		$args['id']        = ( isset( $args['id'] ) ) ? $args['id'] : 'edd_wallet_widget';
		$instance['title'] = ( isset( $instance['title'] ) ) ? $instance['title'] : '';

		$title = apply_filters( 'widget_title', $instance['title'], $instance, $args['id'] );

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		do_action( 'edd_before_wallet_widget' );

		$current_user = wp_get_current_user();

		$value = edd_wallet()->wallet->balance( $current_user->ID );
		$value = edd_currency_filter( edd_format_amount( $value ) );
		$value = '<span class="edd-wallet-value">' . $value . '</span>';

		echo $value;

		do_action( 'edd_after_wallet_widget' );

		echo $args['after_widget'];
	}


	/**
	 * Update widget
	 *
	 * @access      public
	 * @since       1.1.0
	 * @param       array $new_instance The new instance
	 * @param       array $old_instance The old instance
	 * @return      array $instance The updated instance
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']            = strip_tags( $new_instance['title'] );

		return $instance;
	}


	/**
	 * The widget form
	 *
	 * @access      public
	 * @since       1.1.0
	 * @param       array $instance The widget instance
	 * @return      void
	 */
	public function form( $instance ) {
		$defaults = array(
			'title'            => ''
		);

		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'edd-wallet' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo $instance['title']; ?>"/>
		</p>
		<?php
	}
}


/**
 * Register widgets
 *
 * @since       1.0.0
 * @return      void
 */
function edd_wallet_register_widgets() {
	register_widget( 'edd_wallet_widget' );
}
add_action( 'widgets_init', 'edd_wallet_register_widgets' );
