/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 /* eslint-disable max-nested-callbacks */

define([
    'jquery',
    'ko',
    'mage/storage',
    'Magento_Customer/js/model/customer',
    'Magento_GiftCardAccount/js/model/gift-card',
    'Magento_GiftCardAccount/js/model/payment/gift-card-messages',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/error-processor',
    'mage/utils/wrapper',
    'Magento_ReCaptchaWebapiUi/js/webapiReCaptchaRegistry'
], function (
    $,
    ko,
    storage,
    customer,
    giftCardAccount,
    messageList,
    urlBuilder,
    quote,
    errorProcessor,
    wrapper,
    recaptchaRegistry
  ) {
    'use strict';

    var extender = {

        /**
         * @param {Function} originFn - Original method.
         * @param {Object} giftCardCode - giftCardCode model.
         */
        check: function (originFn, giftCardCode) {
            var recaptchaDeferred,
                self = this,
                serviceUrl, headers = {};

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

            this.isLoading(true);

            if (recaptchaRegistry.triggers.hasOwnProperty('recaptcha-checkout-gift-apply')) {
                //ReCaptcha is present for checkout
                recaptchaDeferred = $.Deferred();
                recaptchaRegistry.addListener('recaptcha-checkout-gift-apply', function (token) {
                    headers  = {
                        'X-ReCaptcha': token
                    };

                    storage.get(
                        serviceUrl,  true, 'application/json', headers
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
                });
                //Trigger ReCaptcha validation
                recaptchaRegistry.triggers['recaptcha-checkout-gift-apply']();
                if (
                    !recaptchaRegistry._isInvisibleType.hasOwnProperty('recaptcha-checkout-gift-apply') ||
                    recaptchaRegistry._isInvisibleType['recaptcha-checkout-gift-apply'] === false
                ) {
                    //remove listener so that get gift action is only triggered by the 'Gift Apply' button
                    recaptchaRegistry.removeListener('recaptcha-checkout-gift-apply');
                }

                return recaptchaDeferred;
            }

            return originFn(giftCardCode);
        }
    };

    return function (target) {
        return wrapper.extend(target, extender);
    };
});
