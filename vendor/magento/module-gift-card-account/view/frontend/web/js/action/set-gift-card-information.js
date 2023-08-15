/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    '../model/payment/gift-card-messages',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals'
], function (
    $,
    quote,
    urlBuilder,
    storage,
    messageList,
    errorProcessor,
    customer,
    fullScreenLoader,
    getPaymentInformationAction,
    totals
) {
    'use strict';

    return function (giftCardCode) {
        var serviceUrl,
            payload,
            message = $.mage.__('Gift Card %1 was added.').replace('%1', giftCardCode);

        /**
         * Checkout for guest and registered customer.
         */
        if (!customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/carts/guest-carts/:cartId/giftCards', {
                cartId: quote.getQuoteId()
            });
            payload = {
                cartId: quote.getQuoteId(),
                giftCardAccountData: {
                    'gift_cards': giftCardCode
                }
            };
        } else {
            serviceUrl = urlBuilder.createUrl('/carts/mine/giftCards', {});
            payload = {
                cartId: quote.getQuoteId(),
                giftCardAccountData: {
                    'gift_cards': giftCardCode
                }
            };
        }
        messageList.clear();
        fullScreenLoader.startLoader();
        storage.post(
            serviceUrl, JSON.stringify(payload)
        ).done(function (response) {
            var deferred = $.Deferred();

            if (response) {
                totals.isLoading(true);
                getPaymentInformationAction(deferred);
                $.when(deferred).done(function () {
                    totals.isLoading(false);
                });
                messageList.addSuccessMessage({
                    'message': message
                });
            }
        }).fail(function (response) {
            totals.isLoading(false);
            errorProcessor.process(response, messageList);
        }).always(function () {
            fullScreenLoader.stopLoader();
        });
    };
});
