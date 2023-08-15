/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'ko',
    'Magento_Customer/js/model/address-list',
    'Magento_QuickCheckout/js/model/customer/address-list'
], function (
    ko,
    checkoutAddressList,
    quickCheckoutAddressList
) {
    'use strict';

    var addressOptions = checkoutAddressList(),
        externalAddressOptions = quickCheckoutAddressList(),
        customerHasAddresses = addressOptions.length > 0 || externalAddressOptions.length > 0;

    return function (BillingAddress) {
        return BillingAddress.extend({
            defaults: {
                customerHasAddresses: customerHasAddresses,
                customerHasNewAddresses: ko.observable(customerHasAddresses)
            },

            initialize: function () {
                var self = this;

                this._super();

                quickCheckoutAddressList.subscribe(function (updatedList) {
                    var hasAddresses = updatedList.length > 0;

                    self.customerHasAddresses = hasAddresses;
                    self.customerHasNewAddresses(hasAddresses);
                });

                return this;
            }
        });
    };
});
