/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/mage'
], function ($) {
    'use strict';

    return function (config) {
        var dataForm = $('#form-validate');

        if (config.hasUserDefinedAttributes) {
            dataForm = dataForm.mage('fileElement', {});
        }
        dataForm.mage('validation', config);

        if (config.disableAutoComplete) {
            dataForm.find('input:text').attr('autocomplete', 'off');
        }
    };
});
