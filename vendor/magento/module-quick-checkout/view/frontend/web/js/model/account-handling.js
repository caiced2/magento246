/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/address-converter',
    'Magento_QuickCheckout/js/model/customer/customer',
    'Magento_QuickCheckout/js/model/customer/address'
], function (
    _,
    quote,
    selectShippingAddress,
    selectBillingAddress,
    checkoutData,
    urlBuilder,
    customer,
    addressConverter,
    boltCustomer,
    boltAddress
) {
    'use strict';

    return {
        defaults: {
            editBillingAddressSelector: '.add-new-card-container .action-edit-address',
            sameAddressCheckboxSelector: '#billing-address-same-as-shipping-quick_checkout',
            emailFormSelector: 'form[data-role=email-with-possible-login]',
            addressType: 'bolt-customer-address'
        },

        /**
         * @returns {boolean}
         */
        isBoltCheckoutEnabled: function () {
            return window.checkoutConfig.payment.quick_checkout !== null
                && typeof window.checkoutConfig.payment.quick_checkout !== 'undefined';
        },

        /**
         * @returns {boolean}
         */
        isBoltLoginAvailable: function () {
            return this.isBoltCheckoutEnabled()
                && this.canDisplayOtpPopup()
                && window.checkoutConfig.payment.quick_checkout.isBoltLoginAvailable;
        },

        /**
         * @returns {boolean}
         */
        canDisplayOtpPopup: function () {
            return this.isBoltCheckoutEnabled()
                && window.checkoutConfig.payment.quick_checkout.canDisplayOtpPopup === true;
        },

        /**
         * @returns {boolean}
         */
        isBoltUser: function () {
            return boltCustomer.isBoltUser();
        },

        /**
         * @param data
         */
        setBoltUser: function (data) {
            boltCustomer.setIsLoggedIn(data);
        },

        /**
         * Get Bolt addresses as clean Magento address objects
         *
         * @returns {Object[]}
         */
        getBoltShippingAddresses: function () {
            var newAddress = checkoutData.getNewCustomerShippingAddress(),
                addresses = [];

            if (checkoutData.getBoltAccountDetails()) {
                if (newAddress) {
                    addresses.push(addressConverter.formAddressDataToQuoteAddress(newAddress));
                }
                _.each(checkoutData.getBoltAccountDetails().addresses, function (address) {
                    addresses.push(boltAddress(address));
                });
            }
            return addresses;
        },

        /**
         * Assign and set address in checkout data
         *
         * @param data
         * @param type
         */
        assignAddressData: function (data, type) {
            var addressType = this.defaults.addressType,
                address = type === 'shipping' ? quote.shippingAddress() : quote.billingAddress();

            if (data == null || typeof data == 'undefined') {
                return;
            }

            address = _.extend({}, address, data);

            address.canUseForBilling = function () {
                return true;
            };
            address.getType = function () {
                return addressType;
            };
            address.getKey = function () {
                if (typeof address.extensionAttributes.boltId === 'undefined') {
                    return this.getType() + address.id;
                }
                return this.getType() + address.extensionAttributes.boltId;
            };
            address.getCacheKey = function () {
                return this.getKey();
            };

            if (customer.isLoggedIn()) {
                address.prefix = customer.customerData.prefix;
                address.suffix = customer.customerData.suffix;
            }

            if (type === 'shipping') {
                selectShippingAddress(address);
                checkoutData.setSelectedBoltAddress(address);
                checkoutData.setSelectedShippingAddress(address.getCacheKey());
            } else {
                selectBillingAddress(address);
                checkoutData.setSelectedBillingAddress(address.getCacheKey());
            }
        },

        getBoltCards: function () {
            var cards = [];

            if (checkoutData.getBoltAccountDetails()) {
                _.each(checkoutData.getBoltAccountDetails().payment_methods, function (payment) {
                    cards.push(payment);
                });
            }
            return cards;
        },

        /**
         * Get billing address id
         *
         * @returns {string}
         */
        getBillingAddressBoltId: function () {
            return this.getAddressId(quote.billingAddress());
        },

        /**
         * Get shipping address id
         *
         * @returns {string}
         */
        getShippingAddressBoltId: function () {
            return this.getAddressId(quote.shippingAddress());
        },

        /**
         * Get address id
         *
         * @param {Object} address
         * @returns {string}
         */
        getAddressId: function (address) {
            if (address !== null
                && typeof address !== 'undefined'
                && address.extensionAttributes !== null
                && typeof address.extensionAttributes !== 'undefined'
                && address.extensionAttributes.boltId !== null
                && typeof address.extensionAttributes.boltId !== 'undefined'
            ) {
                return address.extensionAttributes.boltId;
            }
            return '';
        },

        /**
         * Clear all bolt data form checkout data
         */
        clearBoltCheckoutData: function () {
            checkoutData.setQuickCheckoutData(null);
            checkoutData.getQuickCheckoutData();
            boltCustomer.isBoltUser(false);
        },

        /**
         * Clear all base checkout data
         */
        clearCheckoutData: function () {
            checkoutData.setSelectedShippingAddress(null);
            checkoutData.setShippingAddressFromData({});
            checkoutData.setNewCustomerShippingAddress(null);
            checkoutData.setSelectedShippingRate(null);
            checkoutData.setSelectedPaymentMethod(null);
            checkoutData.setSelectedBillingAddress(null);
            checkoutData.setBillingAddressFromData({});
            checkoutData.setNewCustomerBillingAddress(null);
            checkoutData.setValidatedEmailValue('');
            checkoutData.setInputFieldEmailValue('');
            checkoutData.setCheckedEmailValue('');
        },

        /**
         * @returns {boolean}
         */
        isLoggedInMagento: function () {
            return customer.isLoggedIn();
        },

        /**
         * @returns {boolean}
         */
        isLoggedInBolt: function () {
            return boltCustomer.isBoltUser();
        },

        /**
         * @returns {boolean}
         */
        isMagentoNetwork: function () {
            return this.isLoggedInMagento() && !this.isLoggedInBolt();
        },

        /**
         * @returns {String}
         */
        getCustomerEmail: function () {
            if (this.isLoggedInBolt()) {
                return boltCustomer.getEmail();
            } else if (this.isLoggedInMagento()) {
                return customer.customerData.email;
            }
            return '';
        }
    };
});
