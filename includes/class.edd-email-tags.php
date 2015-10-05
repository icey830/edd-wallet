<?php
/**
 * EDD Wallet API for creating Email template tags
 *
 * Email tags are wrapped in { }
 *
 * To replace tags in content, use: edd_wallet_do_email_tags( $content, $type, $payment_id );
 *
 * To add tags, use: edd_wallet_add_email_tag( $tag, $description, $func ). Be sure to wrap edd_wallet_add_email_tag()
 * in a function hooked to the 'edd_wallet_email_tags' action
 *
 * @package     EDD\Wallet\Emails
 * @since       1.0.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EDD_Wallet_Email_Template_Tags {

	/**
	 * Container for storing all tags
	 *
	 * @since	   1.0.0
	 */
	private $tags;


	/**
	 * Payment ID
	 *
	 * @since       1.0.0
	 */
	private $payment_id;


	/**
	 * Add an email tag
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $tag Email tag to be replace in email
	 * @param       callable $func Hook to run when email tag is found
	 * @return      void
	 */
	public function add( $tag, $description, $func ) {
		if( is_callable( $func ) ) {
			$this->tags[$tag] = array(
				'tag'         => $tag,
				'description' => $description,
				'func'        => $func
			);
		}
	}


	/**
	 * Remove an email tag
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $tag Email tag to remove hook from
	 */
	public function remove( $tag ) {
		unset( $this->tags[$tag] );
	}


	/**
	 * Check if $tag is a registered email tag
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $tag Email tag that will be searched
	 * @return      bool
	 */
	public function email_tag_exists( $tag ) {
		return array_key_exists( $tag, $this->tags );
	}


	/**
	 * Returns a list of all email tags
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      array
	 */
	public function get_tags() {
		return $this->tags;
	}


	/**
	 * Search content for email tags and filter email tags through their hooks
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $content Content to search for email tags
	 * @param       int $payment_id The payment id
	 * @return      string Content with email tags filtered out.
	 */
	public function do_tags( $content, $payment_id = 0 ) {

		// Check if there is atleast one tag added
		if ( empty( $this->tags ) || ! is_array( $this->tags ) ) {
			return $content;
		}

		$this->payment_id = $payment_id;

		$new_content = preg_replace_callback( "/{([A-z0-9\-\_]+)}/s", array( $this, 'do_tag' ), $content );

		$this->payment_id = null;

		return $new_content;
	}


	/**
	 * Do a specific tag, this function should not be used. Please use edd_do_email_tags instead.
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $m message
	 * @return      mixed
	 */
	public function do_tag( $m ) {
		// Get tag
		$tag = $m[1];

		// Return tag if tag not set
		if( ! $this->email_tag_exists( $tag ) ) {
			return $m[0];
		}

		return call_user_func( $this->tags[$tag]['func'], $this->payment_id, $tag );
	}
}


/**
 * Add an email tag
 *
 * @since       1.0.0
 * @param       string $tag Email tag to be replace in email
 * @param       string $desc The description of the email tag
 * @param       callable $func Hook to run when email tag is found
 * @return      void
 */
function edd_wallet_add_email_tag( $tag, $description, $func ) {
	edd_wallet()->email_tags->add( $tag, $description, $func );
}


/**
 * Remove an email tag
 *
 * @since       1.0.0
 * @param       string $tag Email tag to remove hook from
 * @return      void
 */
function edd_wallet_remove_email_tag( $tag ) {
	edd_wallet()->email_tags->remove( $tag );
}


/**
 * Check if $tag is a registered email tag
 *
 * @since       1.0.0
 * @param       string $tag Email tag that will be searched
 * @return      bool
 */
function edd_wallet_email_tag_exists( $tag ) {
	return edd_wallet()->email_tags->email_tag_exists( $tag );
}


/**
 * Get all email tags
 *
 * @since       1.0.0
 * @return      array
 */
function edd_wallet_get_email_tags() {
	return edd_wallet()->email_tags->get_tags();
}


/**
 * Get a formatted HTML list of all available email tags
 *
 * @since       1.0.0
 * @return      string
 */
function edd_wallet_get_emails_tags_list() {
	// The list
	$list = '';

	// Get all tags
	$email_tags = edd_wallet_get_email_tags();

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
 * Search content for email tags and filter email tags through their hooks
 *
 * @since       1.0.0
 * @param       string $content Content to search for email tags
 * @param       int $payment_id The payment id
 * @return      string Content with email tags filtered out.
 */
function edd_wallet_do_email_tags( $content, $payment_id = 0 ) {

	// Replace all tags
	$content = edd_wallet()->email_tags->do_tags( $content, $payment_id );

	// Return content
	return $content;
}


/**
 * Load email tags
 *
 * @since       1.0.0
 */
function edd_wallet_load_email_tags() {
	do_action( 'edd_wallet_add_email_tags' );
}
add_action( 'init', 'edd_wallet_load_email_tags', -999 );


/**
 * Add default EDD email template tags
 *
 * @since       1.0.0
 */
function edd_wallet_setup_email_tags() {

	// Setup default tags array
	$email_tags = array(
		array(
			'tag'         => 'name',
			'description' => __( "The buyer's first name", 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_first_name'
		),
		array(
			'tag'         => 'fullname',
			'description' => __( "The buyer's full name, first and last", 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_fullname'
		),
		array(
			'tag'         => 'username',
			'description' => __( "The buyer's user name on the site, if they registered an account", 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_username'
		),
		array(
			'tag'         => 'user_email',
			'description' => __( "The buyer's email address", 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_user_email'
		),
		array(
			'tag'         => 'billing_address',
			'description' => __( 'The buyer\'s billing address', 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_billing_address'
		),
		array(
			'tag'         => 'date',
			'description' => __( 'The date of the purchase', 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_date'
		),
		array(
			'tag'         => 'value',
			'description' => __( 'The value of the deposit or withdrawal', 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_value'
		),
		array(
			'tag'         => 'payment_id',
			'description' => __( 'The unique ID number for this purchase', 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_payment_id'
		),
		array(
			'tag'         => 'receipt_id',
			'description' => __( 'The unique ID number for this purchase receipt', 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_receipt_id'
		),
		array(
			'tag'         => 'payment_method',
			'description' => __( 'The method of payment used for this purchase', 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_payment_method'
		),
		array(
			'tag'         => 'sitename',
			'description' => __( 'Your site name', 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_sitename'
		),
		array(
			'tag'         => 'receipt_link',
			'description' => __( 'Adds a link so users can view their receipt directly on your website if they are unable to view it in the browser correctly.', 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_receipt_link'
		),
		array(
			'tag'         => 'ip_address',
			'description' => __( 'The buyer\'s IP Address', 'edd-wallet' ),
			'function'    => 'edd_wallet_email_tag_ip_address'
		)
	);

	// Apply edd_wallet_email_tags filter
	$email_tags = apply_filters( 'edd_wallet_email_tags', $email_tags );

	// Add email tags
	foreach ( $email_tags as $email_tag ) {
		edd_wallet_add_email_tag( $email_tag['tag'], $email_tag['description'], $email_tag['function'] );
	}

}
add_action( 'edd_wallet_add_email_tags', 'edd_wallet_setup_email_tags' );


/**
 * Email template tag: name
 * The buyer's first name
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string name
 */
function edd_wallet_email_tag_first_name( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		$payment_data = edd_get_payment_meta( $payment_id );
		if( empty( $payment_data['user_info'] ) ) {
			return '';
		}
		$email_name   = edd_get_email_names( $payment_data['user_info'] );
		$name = $email_name['name'];
	} else {
		$item = edd_wallet()->db->get_customer_wallet_item( $payment_id );
		$user_data = get_userdata( $item->user_id );
		$name = $user_data->first_name;
	}

	return $name;
}


/**
 * Email template tag: fullname
 * The buyer's full name, first and last
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string fullname
 */
function edd_wallet_email_tag_fullname( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		$payment_data = edd_get_payment_meta( $payment_id );
		if( empty( $payment_data['user_info'] ) ) {
			return '';
		}
		$email_name   = edd_get_email_names( $payment_data['user_info'] );
		$name   = $email_name['fullname'];
	} else {
		$item = edd_wallet()->db->get_customer_wallet_item( $payment_id );
		$user_data = get_userdata( $item->user_id );
		$name = $user_data->first_name . ' ' . $user_data->last_name;
	}

	return $name;
}


/**
 * Email template tag: username
 * The buyer's user name on the site, if they registered an account
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string username
 */
function edd_wallet_email_tag_username( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		$payment_data = edd_get_payment_meta( $payment_id );
		if( empty( $payment_data['user_info'] ) ) {
			return '';
		}
		$email_name   = edd_get_email_names( $payment_data['user_info'] );
		$name = $email_name['username'];
	} else {
		$item = edd_wallet()->db->get_customer_wallet_item( $payment_id );
		$user_data = get_userdata( $item->user_id );
		$name = $user_data->user_login;
	}

	return $name;
}


/**
 * Email template tag: user_email
 * The buyer's email address
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string user_email
 */
function edd_wallet_email_tag_user_email( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		return edd_get_payment_user_email( $payment_id );
	} else {
		$item = edd_wallet()->db->get_customer_wallet_item( $payment_id );
		$user_data = get_userdata( $item->user_id );
		return $user_data->user_email;
	}
}


/**
 * Email template tag: billing_address
 * The buyer's billing address
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string billing_address
 */
function edd_wallet_email_tag_billing_address( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		$user_info    = edd_get_payment_meta_user_info( $payment_id );
		$user_address = ! empty( $user_info['address'] ) ? $user_info['address'] : array( 'line1' => '', 'line2' => '', 'city' => '', 'country' => '', 'state' => '', 'zip' => '' );

		$return = $user_address['line1'] . "\n";
		if( ! empty( $user_address['line2'] ) ) {
			$return .= $user_address['line2'] . "\n";
		}
		$return .= $user_address['city'] . ' ' . $user_address['zip'] . ' ' . $user_address['state'] . "\n";
		$return .= $user_address['country'];

		return $return;
	} else {
		return '';
	}
}


/**
 * Email template tag: date
 * Date of purchase
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string date
 */
function edd_wallet_email_tag_date( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		$payment_data = edd_get_payment_meta( $payment_id );
		$date = strtotime( $payment_data['date'] );
	} else {
		$item = edd_wallet()->db->get_customer_wallet_item( $payment_id );
		$date = strtotime( $item->date_created );
	}

	return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
}


/**
 * Email template tag: value
 * The total value of the purchase
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string value
 */
function edd_wallet_email_tag_value( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		$value = edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment_id ) ), edd_get_payment_currency_code( $payment_id ) );
	} else {
		$item = edd_wallet()->db->get_customer_wallet_item( $payment_id );
		$value = edd_currency_filter( edd_format_amount( $item->amount ) );
	}

	return html_entity_decode( $value, ENT_COMPAT, 'UTF-8' );
}


/**
 * Email template tag: payment_id
 * The unique ID number for this purchase
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      int payment_id
 */
function edd_wallet_email_tag_payment_id( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		return edd_get_payment_number( $payment_id );
	} else {
		return '';
	}
}


/**
 * Email template tag: receipt_id
 * The unique ID number for this purchase receipt
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string receipt_id
 */
function edd_wallet_email_tag_receipt_id( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		return edd_get_payment_key( $payment_id );
	} else {
		return '';
	}
}


/**
 * Email template tag: payment_method
 * The method of payment used for this purchase
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string gateway
 */
function edd_wallet_email_tag_payment_method( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		return edd_get_gateway_checkout_label( edd_get_payment_gateway( $payment_id ) );
	} else {
		return '';
	}
}


/**
 * Email template tag: sitename
 * Your site name
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string sitename
 */
function edd_wallet_email_tag_sitename( $payment_id ) {
	return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
}


/**
 * Email template tag: receipt_link
 * Adds a link so users can view their receipt directly on your website if they are unable to view it in the browser correctly
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string receipt_link
 */
function edd_wallet_email_tag_receipt_link( $payment_id ) {
	if( get_post_type( $payment_id ) == 'edd_payment' ) {
		return sprintf( __( '%1$sView it in your browser.%2$s', 'edd-wallet' ), '<a href="' . esc_url( add_query_arg( array( 'payment_key' => edd_get_payment_key( $payment_id ), 'edd_action' => 'view_receipt' ), home_url() ) ) . '">', '</a>' );
	} else {
		return '';
	}
}


/**
 * Email template tag: IP address
 * IP address of the customer
 *
 * @since       1.0.0
 * @param       int $payment_id
 * @return      string IP address
 */
function edd_wallet_email_tag_ip_address( $payment_id ) {
	return edd_get_payment_user_ip( $payment_id );
}
