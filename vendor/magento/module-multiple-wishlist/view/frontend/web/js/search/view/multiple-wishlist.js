/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/alert'
], function ($, alert) {
    'use strict';

    return function (options) {
        var form = $('form#' + options.id);

        form.find('button[type="submit"]').on('click', function (event) {
            if (!form.find('input:checkbox:checked').length) {
                alert({
                    content: options.checkBoxValidationMessage
                });
                event.preventDefault();
            }
        });
    };
});
