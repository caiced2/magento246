/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'uiComponent',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/quote'
], function (_, Component, addressList, selectShippingAddressAction, checkoutData, quote) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_CheckoutAddressSearchGiftRegistry/shipping-address/address-renderer/recipientAddress',
            giftRegistryAddress: {},
            isChecked: false
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('isChecked giftRegistryAddress');

            addressList.subscribe(function (addresses) {
                    _.each(addresses, function (address) {
                        if (address.getType() === 'gift-registry' && address.giftRegistryId !== undefined) {
                            this.giftRegistryAddress(address);
                        }
                    }, this);
                }, this);

            quote.shippingAddress.subscribe(function (address) {
                this.isChecked(address.getKey() === this.giftRegistryAddress().getKey() ? 'gift-registry' : false);
            }, this);

            return this;
        },

        /**
         * Select shipping address.
         *
         * @returns {Boolean}
         */
        selectShippingAddress: function () {
            selectShippingAddressAction(this.giftRegistryAddress());
            checkoutData.setSelectedShippingAddress(this.giftRegistryAddress().getKey());

            return true;
        }
    });
});
