/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['ko'], function (ko) {
    'use strict';

    return {
        code: ko.observable(false),
        amount: ko.observable(false),
        isValid: ko.observable(false),
        isChecked: ko.observable(false)
    };
});
