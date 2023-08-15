/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_CheckoutAddressSearch/js/view/shipping-address/selected',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/checkout-data'
], function (_, Selected, addressList, selectShippingAddressAction, checkoutData) {
    'use strict';

    return Selected.extend({
        defaults: {
            template: 'Magento_CheckoutAddressSearchGiftRegistry/shipping-address/address-renderer/default',
            defaultAddressTemplate: 'Magento_CheckoutAddressSearch/shipping-address/selected',
            isChecked: false,
            isDefaultAddressDisabled: false
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('isChecked isDefaultAddressDisabled');

            addressList.subscribe(function (addresses) {
                _.each(addresses, function (address) {
                    if (address.isDefaultShipping()) {
                        this.defaultAddress(address);
                    }
                }, this);

                // disable radio button if there is no default shipping address
                if (!this.defaultAddress()) {
                    this.isDefaultAddressDisabled(true);
                }
            }, this);

            return this;
        },

        /**
         * Select shipping address.
         *
         * @param {Object} data
         * @returns {Boolean}
         */
        selectShippingAddress: function (data) {
            selectShippingAddressAction(data.defaultAddress());
            checkoutData.setSelectedShippingAddress(data.defaultAddress().getKey());

            return true;
        },

        /**
         * On shipping address update.
         *
         * @param {Object} address
         */
        onShippingAddressUpdate: function (address) {
            this.isChecked(address.getType() !== 'gift-registry' ? 'customer-address' : false);
            this.isChecked() && this.defaultAddress(address) && this.isDefaultAddressDisabled(false);
        }
    });
});
