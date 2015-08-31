/*global jQuery, document, edd_wallet_vars*/
jQuery(document).ready(function ($) {
    'use strict';

    // Setup Chosen
    $('.edd-wallet-select-chosen').chosen({
        inherit_select_classes: true
    });

    $('.chosen-choices').on('click', function() {
        $(this).children('li').children('input').attr('placeholder', edd_wallet_vars.type_to_search );
    });
});
