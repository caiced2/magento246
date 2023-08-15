/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_QuickCheckout/js/model/customer/customer'
], function (
    ko,
    customer,
    boltCustomer
) {
    'use strict';

    var isActive = function () {
        return !customer.isLoggedIn() && !boltCustomer.isBoltUser();
    };

    return function (Authentication) {
        return Authentication.extend({
            defaults: {
                isActive: ko.observable(isActive())
            },

            initialize: function () {
                var self = this;

                this._super();
                boltCustomer.isBoltUser.subscribe(function () {
                    self.isActive(isActive());
                });
                return this;
            }
        });
    };
});
