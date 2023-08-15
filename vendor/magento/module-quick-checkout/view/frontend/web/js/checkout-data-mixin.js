/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'jquery/jquery-storageapi'
], function ($, storage) {
    'use strict';

    var cacheKey = 'checkout-data';

    if ($.isEmptyObject($.initNamespaceStorage('mage-cache-storage').localStorage.get(cacheKey))) {
        storage.initStorage();
    }

    return function (checkoutData) {
       /**
        * @override
        */
        var saveData = function (data) {
            storage.set(cacheKey, data);
        },

        /**
         * @override
         */
        initData = function () {
            return {
                'selectedShippingAddress': null,
                'shippingAddressFromData': null,
                'newCustomerShippingAddress': null,
                'selectedShippingRate': null,
                'selectedPaymentMethod': null,
                'selectedBillingAddress': null,
                'billingAddressFromData': null,
                'newCustomerBillingAddress': null,
                // Quick Checkout data
                'quickCheckout': null
            };
        },

        /**
         * @override
         */
        getData = function () {
            var data = storage.get(cacheKey)();

            if ($.isEmptyObject(data)) {
                data = $.initNamespaceStorage('mage-cache-storage').localStorage.get(cacheKey);

                if ($.isEmptyObject(data)) {
                    data = initData();
                    saveData(data);
                }
            }

            return data;
        },

        mixin = {
            getQuickCheckoutData: function () {
                var data = null;

                if (getData().quickCheckout !== null && typeof getData().quickCheckout !== 'undefined') {
                    return getData().quickCheckout;
                }
                data = getData();
                data.quickCheckout = {
                    'boltAccountDetails': null,
                    'selectedPaymentMethod': null,
                    'selectedAddress': null,
                    'isSaveNewCard': true,
                    'isSaveNewAddress': true,
                    'loggedOut': false,
                    'hasBoltAccount': false,
                    'registerWithBolt': true,
                    'isUseExistingCard': ''
                };
                saveData(data);
                return data.quickCheckout;
            },

            setQuickCheckoutData: function (data) {
                var obj = getData();

                obj.quickCheckout = data;
                saveData(obj);
            },

            getBoltAccountDetails: function () {
                return this.getQuickCheckoutData().boltAccountDetails;
            },

            setBoltAccountDetails: function (details) {
                var obj = getData();

                obj.quickCheckout.boltAccountDetails = details;
                saveData(obj);
            },

            getSelectedBoltPaymentMethod: function () {
                return this.getQuickCheckoutData().selectedPaymentMethod;
            },

            setSelectedBoltPaymentMethod: function (paymentMethod) {
                var obj = getData();

                obj.quickCheckout.selectedPaymentMethod = paymentMethod;
                saveData(obj);
            },

            getSelectedBoltAddress: function () {
                return this.getQuickCheckoutData().selectedAddress;
            },

            setSelectedBoltAddress: function (address) {
                var obj = getData();

                obj.quickCheckout.selectedAddress = address;
                saveData(obj);
            },

            getIsSaveNewCard: function () {
                return this.getQuickCheckoutData().isSaveNewCard;
            },

            setIsSaveNewCard: function (save) {
                var obj = getData();

                obj.quickCheckout.isSaveNewCard = save;
                saveData(obj);
            },

            getIsSaveNewAddress: function () {
                return this.getQuickCheckoutData().isSaveNewAddress;
            },

            setIsSaveNewAddress: function (save) {
                var obj = getData();

                obj.quickCheckout.isSaveNewAddress = save;
                saveData(obj);
            },

            getLoggedOut: function () {
                return this.getQuickCheckoutData().loggedOut;
            },

            setLoggedOut: function (logout) {
                var obj = getData();

                obj.quickCheckout.loggedOut = logout;
                saveData(obj);
            },

            getInputFieldEmailValue: function () {
                var obj = getData();

                if (this.getLoggedOut()) {
                    return '';
                }
                return obj.inputFieldEmailValue ? obj.inputFieldEmailValue : '';
            },

            setInputFieldEmailValue: function (email) {
                var obj = getData();

                if (this.getLoggedOut()) {
                    email = '';
                }
                obj.inputFieldEmailValue = email;
                saveData(obj);
            },

            getHasBoltAccount: function () {
                return this.getQuickCheckoutData().hasBoltAccount;
            },

            setHasBoltAccount: function (account) {
                var obj = getData();

                obj.quickCheckout.hasBoltAccount = account;
                saveData(obj);
            },

            getRegisterWithBolt: function () {
                return this.getQuickCheckoutData().registerWithBolt;
            },

            setRegisterWithBolt: function (register) {
                var obj = getData();

                obj.quickCheckout.registerWithBolt = register;
                saveData(obj);
            },

            getIsUseExistingCard: function () {
                return this.getQuickCheckoutData().isUseExistingCard;
            },

            setIsUseExistingCard: function (useExistingCard) {
                var obj = getData();

                obj.quickCheckout.isUseExistingCard = useExistingCard;
                saveData(obj);
            },

            getAccountInformationLoaded: function () {
                return this.getQuickCheckoutData().accountInformationLoaded;
            },

            setAccountInformationLoaded: function (accountInformationLoaded) {
                var obj = getData();

                obj.quickCheckout.accountInformationLoaded = accountInformationLoaded;
                saveData(obj);
            }
        };

        return $.extend(checkoutData, mixin);
    };
});
