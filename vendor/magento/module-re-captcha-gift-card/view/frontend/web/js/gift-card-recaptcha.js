/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable no-unused-vars */
define(
    [
        'Magento_ReCaptchaWebapiUi/js/webapiReCaptcha',
        'Magento_ReCaptchaWebapiUi/js/webapiReCaptchaRegistry',
        'jquery',
        'Magento_GiftCardAccount/js/action/set-gift-card-information',
        'Magento_Checkout/js/model/quote',
        'ko'
    ], function (Component, recaptchaRegistry, $, setGiftCardAction, quote, ko) {
        'use strict';

        var totals = quote.getTotals(),
            GiftCode = ko.observable(null),
            isApplied;

        if (totals()) {
            GiftCode(totals()['gift_code']);
        }

        return Component.extend({

            /**
             * @inheritdoc
             */
            initialize: function () {
                this._super();
                this._loadApi();
            },

            /**
             * Initialize parent form.
             *
             */
            initParentForm: function (parentForm, widgetId) {
                var self = this,
                    captchaId = this.getReCaptchaId();

                this._super();
                if (GiftCode() != null) {
                    if (isApplied) {
                        self.validateReCaptcha(true);
                        $('#' + captchaId).hide();
                    }
                }
            },

            /**
             * Initialize reCAPTCHA after first rendering
             */
            initCaptcha: function () {
                this._super();

                if (!recaptchaRegistry.triggers.hasOwnProperty('recaptcha-checkout-gift-apply')) {
                    $(document).find('input[type=checkbox].required-captcha').prop('checked', true);
                }
            }
        });
    });
