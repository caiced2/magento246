/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'mage/utils/wrapper',
    'Magento_QuickCheckout/js/model/account-handling'
], function (wrapper, bolt) {
    'use strict';

    return function (customerEmailValidator) {
        customerEmailValidator.validate = wrapper.wrap(
            customerEmailValidator.validate, function (originalAction) {
                if (bolt.isBoltUser()) {
                    return true;
                }
                return originalAction();
            });
        return customerEmailValidator;
    };
});
