/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery-ui-modules/widget',
    'mage/validation'
], function ($) {
    'use strict';

    $.widget('mage.giftCard', {
        /**
         * @private
         */
        _create: function () {
            $(this.options.checkStatus).on('click', $.proxy(function () {
                var giftCardStatusId,
                    giftCardSpinnerId,
                    messages,
                    formElement,
                    formData = {},
                    captchaReload;

                if (this.element.validation().valid()) {
                    giftCardStatusId = this.options.giftCardStatusId;
                    giftCardSpinnerId = $(this.options.giftCardSpinnerId);
                    messages = this.options.messages;

                    if (this.options.giftCardFormSelector) {
                        formElement = document.querySelector(this.options.giftCardFormSelector);
                    } else {
                        formElement = $(this.options.giftCardCodeSelector).closest('form');
                    }

                    if (formElement) {
                        $(formElement).find(':input').each(function () {
                            formData[$(this).attr('name')] = $(this).val();
                        });
                    } else {
                        formData['giftcard_code'] = $(this.options.giftCardCodeSelector).val();
                    }
                    $.ajax({
                        url: this.options.giftCardStatusUrl,
                        type: 'post',
                        cache: false,
                        data: formData,

                        /**
                         * Before send.
                         */
                        beforeSend: function () {
                            giftCardSpinnerId.show();
                        },

                        /**
                         * @param {*} response
                         */
                        success: function (response) {
                            $(messages).hide();
                            captchaReload = $('.captcha-reload');

                            if (captchaReload.length) {
                                captchaReload.trigger('click');
                            }
                            $(giftCardStatusId).html(response);
                        },

                        /**
                         * Complete.
                         */
                        complete: function () {
                            giftCardSpinnerId.hide();
                        }
                    });
                }
            }, this));
        }
    });

    return $.mage.giftCard;
});
