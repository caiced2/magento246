/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mageUtils'
], function ($, utils) {
    'use strict';

    return function (config) {
        var dobElement = null,
            errorClass = null;

        if (config.hasUserDefinedAttributes || config.isDobEnabled) {
            return utils.extend(config, {
                /**
                 * @param {jQuery} error
                 * @param {jQuery} element
                 */
                errorPlacement: function (error, element) {
                    if (element.prop('id').search('full') !== -1) {
                        dobElement = $(element).parents('.customer-dob');
                        errorClass = error.prop('class');
                        error.insertAfter(element.parent());
                        dobElement.find('.validate-custom').addClass(errorClass)
                            .after('<div class="' + errorClass + '"></div>');
                    } else {
                        error.insertAfter(element);
                    }
                }
            });
        }
    };
});
