/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    '../model/payment/gift-card-messages',
    'Magento_GiftCardAccount/js/model/gift-card',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/error-processor'
], function (ko, urlBuilder, storage, messageList, giftCardAccount, customer, quote, errorProcessor) {
    'use strict';

    return {
        isLoading: ko.observable(false),

        /**
         * @param {*} giftCardCode
         */
        check: function (giftCardCode) {
            var self = this,
                serviceUrl;

            this.isLoading(true);

            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/carts/guest-carts/:cartId/checkGiftCard/:giftCardCode', {
                    cartId: quote.getQuoteId(),
                    giftCardCode: giftCardCode
                });
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/checkGiftCard/:giftCardCode', {
                    giftCardCode: giftCardCode
                });

            }
            messageList.clear();
            storage.get(
                serviceUrl, false
            ).done(function (response) {
                giftCardAccount.isChecked(true);
                giftCardAccount.code(giftCardCode);
                giftCardAccount.amount(response);
                giftCardAccount.isValid(true);
            }).fail(function (response) {
                giftCardAccount.isValid(false);
                errorProcessor.process(response, messageList);
            }).always(function () {
                self.isLoading(false);
            });
        }
    };
});
