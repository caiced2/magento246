/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_QuickCheckout/js/action/check-existing-account',
    'Magento_QuickCheckout/js/model/account-handling',
    'Magento_QuickCheckout/js/model/customer/customer',
    'Magento_Customer/js/model/customer',
    'Magento_QuickCheckout/js/model/event-tracking',
    'Magento_QuickCheckout/js/model/bolt-embed',
    'Magento_QuickCheckout/js/action/otp-login'
], function (
    $,
    ko,
    quote,
    checkoutData,
    boltAccountDataAction,
    bolt,
    boltCustomer,
    customer,
    eventTracking,
    boltEmbed,
    otpLogin
) {
    'use strict';

    return function (Email) {
        return Email.extend({

            defaults: {
                isAutoLoginEnabled: window.checkoutConfig.payment['quick_checkout'].isAutoLoginEnabled
            },

            // if the otp popup cannot be displayed, the shopper cannot be logged automatically
            isAutoLoginCheckComplete: ko.observable(!bolt.canDisplayOtpPopup()),

            /**
             * @override
             */
            isCustomerLoggedIn: ko.observable(customer.isLoggedIn() || boltCustomer.isBoltUser()),

            /**
             * Initializes observable properties of instance
             *
             * @returns {Object} Chainable.
             */
            initObservable: function () {
                var self = this;

                this._super();
                boltCustomer.isBoltUser.subscribe(function () {
                    self.isCustomerLoggedIn(customer.isLoggedIn() || boltCustomer.isBoltUser());
                });
                return this;
            },

            /**
             * Callback on changing email property
             */
            emailHasChanged: function () {
                if (!this.isAutoLoginCheckComplete()) {
                    this.checkAutoLogin();
                    return;
                }
                this._super();
                this.isCustomerLoggedIn(customer.isLoggedIn() || boltCustomer.isBoltUser());
            },

            /**
             * @override
             * Check email existing
             */
            checkEmailAvailability: function () {
                this._super();
                eventTracking.sendDetailEntryBeganEvent();
                if (bolt.canDisplayOtpPopup() && !bolt.isBoltUser()) {
                    $.when(this.isEmailCheckComplete).always(function () {
                        this.checkBoltAccountExists();
                    }.bind(this));
                }
            },

            /**
             * Check subscription status
             */
            checkBoltAccountExists: function () {
                if (quote.guestEmail) {
                    boltAccountDataAction(quote.guestEmail, this.isPasswordVisible());
                    checkoutData.setLoggedOut(false);
                }
            },

            /**
             * Checks if shopper can be automatically logged using Bolt's active session
             */
            checkAutoLogin: function () {
                var self = this,
                    fieldSelector = '#customer-email-fieldset',
                    authorizationCode = '',
                    authComponent = boltEmbed.createAuthComponent();

                this.isLoading(true);

                if (!this.isAutoLoginEnabled) {
                    this.isLoading(false);
                    this.isAutoLoginCheckComplete(true);
                    return;
                }

                authComponent.mount(fieldSelector).then(function () {
                    authComponent.hasAccount().then(function (account) {
                        if (!account.status) {
                            self.isLoading(false);
                            self.isAutoLoginCheckComplete(true);
                            return;
                        }
                        authComponent.authorize({}).then(function (authorizeResult) {
                            authComponent.unmount();
                            self.isLoading(false);
                            self.isAutoLoginCheckComplete(true);
                            if (typeof authorizeResult !== 'undefined') {
                                authorizationCode = authorizeResult.authorizationCode;
                                otpLogin(authorizationCode, true);
                            }
                        });
                    });
                });
            }
        });
    };
});
