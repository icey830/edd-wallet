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
