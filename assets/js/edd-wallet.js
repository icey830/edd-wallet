/*global jQuery, document, edd_wallet_vars*/
jQuery(document).ready(function ($) {
    'use strict';

    $("input[name='edd_wallet_deposit_amount']").change(function () {
        if ($(this).val() === 'custom') {
            $("input[name='edd_wallet_custom_deposit']").css('display', 'block');
        }
    }).change();

    $("input[name='edd_wallet_deposit']").click(function (e) {
        if ($("input[name='edd_wallet_deposit_amount']:checked").val() === 'custom') {
        	var value = $("input[name='edd_wallet_custom_deposit']").val();

            if (value === '' || isNaN(value) || value <= 0) {
                $('#edd_wallet_error_wrapper').html( '<span class="edd_error">' + edd_wallet_vars.custom_deposit_error + '</span>' );
                $('#edd_wallet_error_wrapper').show();
                e.preventDefault();
            }
        }
    });
});
