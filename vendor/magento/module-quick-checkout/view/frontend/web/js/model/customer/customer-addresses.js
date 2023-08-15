/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_QuickCheckout/js/model/customer/address',
    'Magento_QuickCheckout/js/model/account-handling'
], function (_, Address, bolt) {
    'use strict';

    return {
        /**
         * @return {Array}
         */
        getAddressItems: function () {
            var items = [],
                boltAddresses = [];

            if (bolt.isBoltUser()) {
                boltAddresses = bolt.getBoltShippingAddresses();
                if (boltAddresses.length) {
                    _.each(boltAddresses, function (item) {
                        items.push(item);
                    });
                }
            }
            return items;
        }
    };
});
