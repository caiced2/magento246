/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_CheckoutAddressSearch/js/view/billing-address',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/action/select-billing-address'
], function (_, Component, quote, checkoutData, addressList, selectBillingAddress) {
    'use strict';

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();

            quote.billingAddress.subscribe(function (addressOption) {
                if (!addressOption) {
                    _.each(addressList(), function (address) {
                        if (address.isDefaultBilling && address.isDefaultBilling()) {
                            selectBillingAddress(address);
                            checkoutData.setSelectedBillingAddress(address.getKey());
                        }
                    }, this);
                }
            }, this);

            return this;
        }
    });
});
