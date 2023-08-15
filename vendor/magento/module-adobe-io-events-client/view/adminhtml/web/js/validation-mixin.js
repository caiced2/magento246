/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/lib/validation/utils'
], function ($, utils) {
    'use strict';

    return function () {
        $.validator.addMethod(
            'validate-instance-id',
            function (value) {
                return utils.isEmptyNoTrim(value) || /^[A-Za-z0-9_-]+$/.test(value);
            },
            $.mage.__('Instance ID can contain only alphanumeric characters, underscores, and hyphens.')
        );
    };
});
