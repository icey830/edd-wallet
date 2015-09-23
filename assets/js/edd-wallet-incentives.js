/*global jQuery, document, edd_wallet_incentives_vars*/
jQuery(document).ready(function ($) {
    'use strict';

    // Handle discount on gateway change
    $('input[name="payment-mode"]').change(function(e) {
        var gateway;

        gateway = $(this).val();

        var data = {
            action: 'edd_wallet_process_incentive',
            gateway: gateway
        };

        $.ajax({
        	type: "POST",
        	data: data,
        	url: edd_wallet_incentives_vars.ajaxurl
        });
    });
});