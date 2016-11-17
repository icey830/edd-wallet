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

    $("#edd_wallet_apply_funds").click(function (e) {
        e.preventDefault();

        var value = $(this).data('wallet-value');

        var data = {
            action: 'edd_wallet_process_apply',
            value: value
        };

        $.ajax({
            type: 'POST',
            data: data,
            dataType: 'json',
            url: edd_wallet_vars.ajaxurl,
            success: function (response) {
                //console.log(response.total_raw);
                $('#edd_checkout_cart_form').replaceWith(response.html);
                $('.edd_cart_amount').html(response.total);
                $('.edd_cart_amount').attr('data-total', response.total_raw);
                $('.edd_cart_wallet_row').remove();
            }
        }).fail(function (data) {
            if (window.console && window.console.log) {
                console.log(data);
            }
        });
    });
});
