/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'Magento_Customer/js/model/address-list',
    'mage/translate',
    'Magento_Customer/js/model/customer',
    'Magento_QuickCheckout/js/model/customer/address-list',
    'Magento_QuickCheckout/js/model/account-handling',
    'Magento_QuickCheckout/js/model/customer/customer'
], function (ko, Component, addressList, $t, customer, boltAddressList, bolt, boltCustomer) {
    'use strict';

    /**
     * @override
     * Override the variables for use in the mixin
     */
    var newAddressOption = {
            getAddressInline: function () {
                return $t('New Address');
            },
            customerAddressId: null
        },
        addressOptions = addressList().filter(function (address) {
            return address.getType() === 'customer-address';
        }),
        addressDefaultIndex = addressOptions.findIndex(function (address) {
            return address.isDefaultBilling();
        }),

        concatAddresses = function () {
            var addresses = addressOptions;

            addresses = addresses.concat(boltAddressList());
            if (addresses.length > 0) {
                addresses.push(newAddressOption);
            }
            return addresses;
        };

    return function (List) {
        return List.extend({
            defaults: {
                billingAddressListSelector: 'select[name="billing_address_id"]',
                billingAddressListOptionsSelector: 'select[name="billing_address_id"] option',
                template: 'Magento_Checkout/billing-address',
                selectedAddress: null,
                isNewAddressSelected: false,
                addressOptions: concatAddresses(),
                exports: {
                    selectedAddress: '${ $.parentName }:selectedAddress'
                },
                tracks: {
                    addressOptions: true
                }
            },

            /**
             * @returns {Object} Chainable.
             */
            initConfig: function () {
                this._super();

                //clean up address list
                this.addressOptions = this.addressOptions.filter(function (address) {
                    return address.getAddressInline() !== $t('New Address');
                });
                this.addressOptions.push(newAddressOption);

                return this;
            },

            /**
             * @inheritdoc
             */
            initialize: function () {
                var self = this;

                this._super();
                boltCustomer.isBoltUser.subscribe(function () {
                    if (boltCustomer.isBoltUser()) {
                        boltAddressList(bolt.getBoltShippingAddresses());
                        self.addressOptions = self.concatAddresses();
                    }
                });

                return this;
            },

            /**
             * @override
             * @return {exports.initObservable}
             */
            initObservable: function () {
                this._super()
                    .observe('selectedAddress isNewAddressSelected')
                    .observe({
                        isNewAddressSelected: !boltCustomer.isBoltUser() || !this.addressOptions.length,
                        selectedAddress: this.addressOptions[addressDefaultIndex]
                    });
                return this;
            },

            /**
             * @override
             * Override the onChange binding for proper use
             * @param {Object} address
             */
            onAddressChange: function (address) {
                this.isNewAddressSelected(address === newAddressOption);
            },

            /**
             * Dynamically concat billing addresses
             *
             * @returns {array[]}
             */
            concatAddresses: function () {
                return concatAddresses();
            }
        });
    };
});
