/**
 * AdminGws client side validation rules
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

/* eslint-disable strict */
define([
    'jquery',
    'mage/backend/validation'
], function ($) {

    $.validator.addMethod('validate-one-gws-store', function () {
        if ($('#gws_is_all').val() == 1) { //eslint-disable-line eqeqeq
            return true; // not touching valid intentionally
        }

        return $('.validate-one-gws-store:checked').length;
    }, 'Please select one of the options.');

    $.widget('mage.validation', $.mage.validation, {
        options: {
            /**
             * @param {jQuery} error
             * @param {jQuery} element
             */
            errorPlacement: function (error, element) {
                if (element.is('[name="gws_store_groups[]"]')) {
                    error.insertAfter($('#gws_container').last());
                } else {
                    error.insertAfter(element);
                }
            }
        }
    });
});
