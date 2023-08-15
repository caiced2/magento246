/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'mage/translate',
    'uiComponent',
    'Magento_Ui/js/modal/alert',
    'domReady!'
], function ($, _, $t, Component, alert) {
    'use strict';

    return Component.extend({
        defaults: {
            generalErrorMessage: $t('An error occurred. Refresh the page and try again.'),
            orderFormSelector: '#edit_form',
            containerSelector: '#payment_form_quick_checkout',
            additionalDataSelector: '#quick-checkout-additional-data',
            creditCardFormSelector: '#bolt-credit-card-form',
            quickCheckoutMethodRadioSelector: 'input[value="quick_checkout"]',
            paymentField: null,
            publishableKey: null,
            locale: '',
            creditCardFormConfig: [],
            hostedCreditCard: null
        },

        /** @inheritdoc */
        initialize: function (config, element) {
            var boltEmbedded = window.Bolt(config.publishableKey, {language: config.locale});

            this.element = element;
            this.publishableKey = config.publishableKey;
            this.locale = config.locale;
            this.creditCardFormConfig = config.creditCardFormConfig;

            _.bindAll(
                this,
                'submitForm',
                'getPaymentData',
                'onError',
                'onChangePaymentMethod'
            );

            this._super();
            this.paymentField = boltEmbedded.create('payment_component', this.creditCardFormConfig);
            this.initFormListeners();
            this.reinitializeFormAfterReloads();
            return this;
        },

        /**
         * Initialize form submit listeners.
         */
        initFormListeners: function () {
            this.orderForm = $(this.orderFormSelector);
            this.orderForm.off('changePaymentMethod.' + this.code);
            this.orderForm.on('changePaymentMethod.' + this.code, this.onChangePaymentMethod);
        },

        /**
         * Initialize cc form
         */
        mountCreditCardForm: function () {
            $('body').trigger('processStart');
            this.paymentField.unmount();
            this.paymentField.mount(this.creditCardFormSelector);
            $('body').trigger('processStop');

            this.paymentField.on('inputSubmitRequest', function () {
                this.orderForm.trigger('submitOrder');
            }.bind(this));
        },

        /**
         * Reinitialize submitOrder event
         */
        reinitializeFormAfterReloads: function () {
            if ($(this.quickCheckoutMethodRadioSelector).is(':checked')) {
                this.orderForm.off('beforeSubmitOrder.' + this.code);
                this.orderForm.on('beforeSubmitOrder.' + this.code, this.submitForm);
                this.mountCreditCardForm();
            }
        },

        /**
         * Reinitialize submitOrder event on delegate.
         *
         * @param {Object} event
         * @param {String} method
         */
        onChangePaymentMethod: function (event, method) {
            this.orderForm.off('beforeSubmitOrder.' + this.code);
            if (method === this.code) {
                this.orderForm.on('beforeSubmitOrder.' + this.code, this.submitForm);
                this.mountCreditCardForm();
            }
        },

        /**
         * Form submit handler
         *
         * @param {Object} e
         */
        submitForm: function (e) {
            if (this.orderForm.valid()) {
                this.getPaymentData();
            } else {
                $('body').trigger('processStop');
            }
            e.stopImmediatePropagation();
            return false;
        },

        /**
         * @inheritdoc
         */
        getPaymentData: function () {
            this.paymentField.tokenize().then(function (data) {
                $(this.additionalDataSelector).val(JSON.stringify(data));
                this.orderForm.trigger('realOrder');
            }.bind(this)).catch(this.onError);
        },

        /**
         * Error callback
         */
        onError: function () {
            var message = this.generalErrorMessage;

            $('body').trigger('processStop');
            alert({
                content: message
            });
        }
    });
});
