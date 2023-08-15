/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'mage/storage',
    'ko',
    'uiComponent',
    'Magento_QuickCheckout/js/model/customer/customer',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_QuickCheckout/js/model/account-handling',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/model/messageList',
    'Magento_QuickCheckout/js/action/get-account-details',
    'Magento_QuickCheckout/js/model/event-tracking',
    'Magento_QuickCheckout/js/action/trigger-otp-popup',
    'Magento_QuickCheckout/js/model/bolt-embed'
], function (
    $,
    $t,
    storage,
    ko,
    Component,
    boltCustomer,
    quote,
    checkoutData,
    accountHandling,
    fullScreenLoader,
    customer,
    customerData,
    globalMessageList,
    getAccountDetails,
    eventTracker,
    triggerOtpPopup,
    boltEmbed
) {
    'use strict';

    var welcomeBackMsg = $t('Please log in to your Bolt Wallet to complete payment as'),
        loggedInBoltMsg = $t('Logged in to Bolt as'),
        infoMsg = ko.observable(accountHandling.isMagentoNetwork() ? welcomeBackMsg : loggedInBoltMsg),
        customerEmail = ko.observable(accountHandling.getCustomerEmail()),
        visible = ko.observable(accountHandling.isLoggedInBolt() || accountHandling.isBoltLoginAvailable());

    return Component.extend({
        defaults: {
            template: 'Magento_QuickCheckout/logout-info',
            visible: visible,
            isLoggedInBolt: boltCustomer.isBoltUser,
            isBoltLoginAvailable: accountHandling.isBoltLoginAvailable(),
            infoMsg: infoMsg,
            customerEmail: customerEmail,
            ajaxLogoutUrl: 'customer/ajax/logout',
            ajaxBoltLogoutUrl: 'quick-checkout/ajax/logout'
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            var self = this;

            this._super();
            this.registerEventListeners();

            boltCustomer.isBoltUser.subscribe(function (isBoltUser) {
                if (isBoltUser) {
                    self.infoMsg(loggedInBoltMsg);
                    self.customerEmail(boltCustomer.getEmail());
                    self.visible(true);
                }
            });

            if (
                boltCustomer.isLoggedIn() &&
                !checkoutData.getAccountInformationLoaded()) {
                getAccountDetails();
            }

            return this;
        },

        /**
         * Register document event listeners
         */
        registerEventListeners: function () {
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'hidden') {
                    eventTracker.sendExitEvent();
                }
            });
        },

        /**
         * Login to Bolt
         */
        loginBolt: function () {
            var email = this.customerEmail(),
                otpContainerSelector = '.bolt-logout-info';

            if (!email) {
                return;
            }

            triggerOtpPopup(email, otpContainerSelector);
        },

        /**
         * Logout Bolt user and clean up checkout data
         */
        logoutBolt: function () {
            var self = this,
                logoutUrl = customer.isLoggedIn() ? self.ajaxLogoutUrl : self.ajaxBoltLogoutUrl,
                defaultError = $t('Could not logout. Please try again later.');

            fullScreenLoader.startLoader();

            boltEmbed.logout().then(function () {
                accountHandling.clearCheckoutData();
                accountHandling.clearBoltCheckoutData();
                checkoutData.setLoggedOut(true);

                storage.get(
                    logoutUrl
                ).done(function () {
                    customerData.invalidate(['customer']);
                    window.location = window.checkoutConfig.checkoutUrl;
                }).fail(function () {
                    globalMessageList.addErrorMessage({'message': defaultError});
                }).always(function () {
                    fullScreenLoader.stopLoader();
                });
            });
        }
    });
});
