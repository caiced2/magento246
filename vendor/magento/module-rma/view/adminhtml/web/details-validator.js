/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

(function (factory) {
    'use strict';

    if (typeof define !== 'undefined' && define.amd) {
        define([
            'jquery',
            'mage/backend/validation',
            'mage/mage',
            'mage/translate',
            'Magento_Rma/rma'
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($) {
    'use strict';

    var rma = window.rma;

    if (rma === undefined) {
        return;
    }

    /* eslint-disable max-nested-callbacks */
    rma.addLoadProductsCallback(function () {
        $('[class^="rma-action-links-"]').each(function (el, val) {
            var className = false;

            $(val).attr('class').split(' ').each(function (element) {
                if (element.search(/rma-action-links-/i) !== -1) {
                    className = element;
                }
            });
            $.validator.addMethod(
                className,
                function (v, elem) {
                    var isValid = true,
                        columnId = $(elem).parents().children('[id^=itemDiv_]').attr('id');

                    $('#' + columnId).find('.mage-error').each(function (element, value) {
                        if ($(value).css('display') != 'none') { //eslint-disable-line eqeqeq
                            isValid = false;
                        }
                    });

                    return isValid;
                },
                $.mage.__('Click Details for more required fields.')
            );
        });
    });

    /* eslint-enable max-nested-callbacks */
}));
