/*global jQuery, window, document, console, edd_wallet_incentives_vars*/
jQuery(document).ready(function ($) {
    'use strict';

    // Handle discount on gateway change
    $('input[name="payment-mode"]').change(function () {
        var gateway;

        gateway = $(this).val();

        var data = {
            action: 'edd_wallet_process_incentive',
            gateway: gateway
        };

        $.ajax({
            type: 'POST',
            data: data,
            dataType: 'json',
            url: edd_wallet_incentives_vars.ajaxurl,
            success: function (response) {
                console.log(response);
                $('#edd_checkout_cart_form').replaceWith(response.html);
            }
        }).fail(function (data) {
            if (window.console && window.console.log) {
                console.log(data);
            }
        });
    });
});