<?php
/**
 * Shortcodes
 *
 * @package     EDD\Wallet\Shortcodes
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Deposit page shortcode
 *
 * @since       1.0.0
 * @param       array $atts Shortcode attributes
 * @param       string $content Shortcode content
 * @global      object $post The WordPress post object
 * @return      string Deposit page
 */
function edd_wallet_deposit_shortcode( $atts, $content = null ) {
    global $post;

    $post_id = is_object( $post ) ? $post->ID : 0;

    $atts = shortcode_atts( array(
        'button_text'       => __( 'Make Deposit', 'edd-wallet' ),
        'button_style'      => edd_get_option( 'button_style', 'button' ),
        'button_color'      => edd_get_option( 'checkout_color', 'blue' ),
        'button_class'      => 'edd-submit'
    ), $atts, 'edd_deposit' );

    // Override color if color == inherit
    if( isset( $atts['button_color'] ) ) {
        $atts['button_color'] = ( $atts['button_color'] == 'inherit' ) ? '' : $atts['button_color'];
    }

    // Setup straight to gateway if possible
    if( edd_shop_supports_buy_now() ) {
        $atts['direct'] = true;
    } else {
        $atts['direct'] = false;
    }

    $levels     = edd_get_option( 'edd_wallet_deposit_levels', array( '20', '40', '60', '80', '100', '200', '500' ) );
    sort( $levels );

    ob_start();
    ?>
    <form id="edd_wallet_deposit" class="edd_wallet_deposit_form" method="post">
        <?php do_action( 'edd_wallet_deposit_page_top', $atts ); ?>

        <div id="edd_wallet_deposit_amount_wrapper">
            <ul>
                <?php
                foreach( $levels as $id => $level ) {
                    $checked = ( $id == 0 ) ? ' checked="checked"' : '';

                    echo '<li>';
                    echo '<label><input type="radio" id="edd_wallet_deposit_amount" name="edd_wallet_deposit_amount" value="' . $level . '"' . $checked . '> ' . edd_currency_filter( edd_format_amount( $level ) ) . '</label>';
                    echo '</li>';
                }
                ?>
            </ul>
        </div>

        <div class="edd_wallet_deposit_submit_wrapper">
            <?php
            $class = implode( ' ', array( $atts['button_style'], $atts['button_color'], trim( $atts['button_class'] ) ) );

            wp_nonce_field( 'edd-wallet-deposit-nonce' );
            echo '<input type="hidden" name="edd_action" value="wallet_process_deposit" />';
            echo '<input type="submit" class="edd-wallet-deposit ' . esc_attr( $class ) . '" name="edd_wallet_deposit" value="' . esc_attr( $atts['button_text'] ) . '" />';
            ?>
        </div>
        <?php do_action( 'edd_wallet_deposit_page_bottom', $atts ); ?>
    </form>
    <?php
    $deposit_form = ob_get_clean();

    return apply_filters( 'edd_wallet_deposit_form', $deposit_form, $atts );
}
add_shortcode( 'edd_deposit', 'edd_wallet_deposit_shortcode' );
