/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
        'mage/translate',
        'mage/storage',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_QuickCheckout/js/model/account-handling',
        'Magento_QuickCheckout/js/action/select-cheapest-shipping-rate',
        'Magento_QuickCheckout/js/action/navigate-to-payment',
        'Magento_QuickCheckout/js/model/customer/address',
        'Magento_QuickCheckout/js/action/set-account-details'
    ], function (
        $t,
        storage,
        quote,
        messageList,
        checkoutData,
        urlBuilder,
        fullScreenLoader,
        bolt,
        selectCheapestShippingAction,
        navigateToPaymentAction,
        boltAddress,
        setAccountDetails
    ) {
        'use strict';

        return function () {
            var payload = {},
                errorMessage = $t('Something went wrong while retrieving your account details. Please try again later.'); // eslint-disable-line max-len

            fullScreenLoader.startLoader();

            return storage.post(
                urlBuilder.createUrl('/quick-checkout/account-details', {}),
                JSON.stringify(payload),
                false
            ).done(
                function (response) {
                    setAccountDetails(response);
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
                }
            ).fail(
                function () {
                    messageList.addErrorMessage({message: errorMessage});
                }
            );
        };
    }
);
