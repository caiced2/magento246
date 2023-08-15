/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'jquery',
    'underscore',
    'mage/translate',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/checkout-data',
    'Magento_QuickCheckout/js/model/account-handling',
    'Magento_QuickCheckout/js/model/customer/customer',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_QuickCheckout/js/action/check-existing-account',
    'Magento_QuickCheckout/js/action/re-authenticate',
    'Magento_QuickCheckout/js/model/errors'
], function (
    ko,
    $,
    _,
    $t,
    quote,
    Component,
    loader,
    checkoutData,
    bolt,
    boltCustomer,
    selectPaymentMethodAction,
    checkExistingAccount,
    reAuthenticate,
    errors
) {
    'use strict';

    return Component.extend({
        defaults: {
            placeOrderTitle: $t('Place Order'),
            generalErrorMessage: $t('An error occurred. Refresh the page and try again.'),
            addNewCard: $t('Add a New Card'),
            useExistingCard: $t('Use an Existing Card'),
            saveCardToBolt: $t('Save this card/address to my Bolt account'),
            saveCardToBoltTooltip: $t('These new credit card details will be stored in your Bolt wallet along with the corresponding billing address when you place your order.'), // eslint-disable-line max-len
            template: 'Magento_QuickCheckout/payment/credit-card',
            isCardNew: true,
            creditCardFormSelector: '#bolt-credit-card-form',
            savedCardSelector: 'select[name="card_list"]',
            registerWithBolt: null,
            registerWithBoltCheckboxSelector: '#register-with-bolt',
            useSavedCardSelector: 'input[name="use_existing_card"]:checked',
            editBillingAddressSelector: '.add-new-card-container .action-edit-address',
            sameAddressCheckboxSelector: '#billing-address-same-as-shipping-quick_checkout',
            saveToBoltCheckboxSelector: '#save-card-to-bolt',
            checkoutSelector: '#checkout',
            selectedCard: ko.observable(checkoutData.getSelectedBoltPaymentMethod()),
            savedCards: ko.observable(bolt.getBoltCards()),
            useSavedCard: ko.observable(bolt.getBoltCards().length > 0),
            isBoltUser: boltCustomer.isBoltUser,
            hasBoltAccount: boltCustomer.hasBoltAccount,
            canDisplayOtpPopup: bolt.canDisplayOtpPopup(),
            isUseExistingCard: ko.observable(function () {
                if (checkoutData.getIsUseExistingCard() !== '') {
                    return checkoutData.getIsUseExistingCard();
                }
                if (bolt.getBoltCards().length > 0) {
                    return 'yes';
                }
                return 'no';
            }()),
            isSaveNewCard: ko.observable(boltCustomer.getHasWriteAccess() && checkoutData.getIsSaveNewCard()),
            creditCardComponentConfig: window.checkoutConfig.payment.quick_checkout.creditCardComponentConfig,
            boltEmbedded: window.Bolt(
                window.checkoutConfig.payment.quick_checkout.publishableKey,
                {
                    language: window.checkoutConfig.payment.quick_checkout.locale
                }
            ),
            paymentField: null,
            accountCheckbox: null,
            boltWalletError: ko.observable('')
        },

        /**
         * Event binding function for select menu
         */
        getDefaultCardId: function () {
            if (bolt.getBoltCards().length > 0
                && checkoutData.getSelectedBoltPaymentMethod() !== null
                && typeof checkoutData.getSelectedBoltPaymentMethod() !== 'undefined'
            ) {
                return checkoutData.getSelectedBoltPaymentMethod().id;
            }
            return false;
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            var self = this;

            _.bindAll(this, 'onSuccess', 'onError');
            this._super();

            boltCustomer.isBoltUser.subscribe(function () {
                self.savedCards(bolt.getBoltCards());
                self.setDefaultBoltPaymentData();
                if (boltCustomer.isBoltUser() && checkoutData.getSelectedPaymentMethod() === self.getCode()) {
                    selectPaymentMethodAction({method: self.getCode()});
                }
            });

            boltCustomer.hasWriteAccess.subscribe(function () {
                self.isSaveNewCard(boltCustomer.getHasWriteAccess() && checkoutData.getIsSaveNewCard());
            });

            this.hasBoltAccount.subscribe(function () {
                this.renderConsentCheckbox();
            }.bind(this));

            return this;
        },

        /**
         * @inheritdoc
         */
        getCode: function () {
            return 'quick_checkout';
        },

        /**
         * @inheritdoc
         */
        getData: function () {
            var billingAddressId = bolt.getBillingAddressBoltId(),
                shippingAddressId = bolt.getShippingAddressBoltId(),
                addNewCard = checkoutData.getIsSaveNewCard(),
                addNewAddress = checkoutData.getIsSaveNewAddress(),
                isExternalShippingAddress = typeof quote.shippingAddress().extensionAttributes !== 'undefined'
                    && quote.shippingAddress().extensionAttributes.boltId !== 'undefined'
                    &&  quote.shippingAddress().extensionAttributes.boltId === null;

            if (!bolt.isBoltUser() || addNewCard === null || typeof addNewCard === 'undefined') {
                addNewCard = false;
            }

            if (!bolt.isBoltUser()
                || addNewAddress === null
                || typeof addNewAddress === 'undefined'
                || isExternalShippingAddress
            ) {
                addNewAddress = false;
            }

            return {
                method: this.item.method,
                'additional_data': {
                    'logged_in_with_bolt': bolt.isBoltUser(),
                    'card': JSON.stringify(this.selectedCard()),
                    'is_card_new': this.isCardNew,
                    'register_with_bolt': this.registerWithBolt,
                    'add_new_card': this.isCardNew && addNewCard,
                    'billing_address_id': billingAddressId,
                    'add_new_address': addNewAddress,
                    'shipping_address_id': shippingAddressId
                }
            };
        },

        /**
         * Render credit card form
         */
        afterCreditCardContainerRender: function () {
            var self = this;


            this.setDefaultBoltPaymentData();

            this.paymentField = this.boltEmbedded.create('payment_component', this.creditCardComponentConfig);

            this.paymentField.mount(this.creditCardFormSelector);

            $(this.checkoutSelector).on('processStop', function () {
                self.savedCards(bolt.getBoltCards());
                if (typeof $(self.useSavedCardSelector).val() === 'undefined') {
                    self.isUseExistingCard('no');
                    checkoutData.setIsUseExistingCard('no');
                }
            });
        },

        /**
         * After render consent container
         */
        afterConsentContainerRender: function () {
            var email = quote.guestEmail !== null ? quote.guestEmail : window.customerData.email;

            if (email) {
                checkExistingAccount(email, false).then(function () {
                    this.hasBoltAccount(boltCustomer.hasBoltAccount());
                    this.renderConsentCheckbox();
                }.bind(this));
            }
        },

        /**
         * Render consent checkbox
         */
        renderConsentCheckbox: function () {
            var shouldRegister = checkoutData.getRegisterWithBolt();

            if (this.accountCheckbox !== null) {
                this.accountCheckbox.unmount();
            }
            if (!this.hasBoltAccount()) {
                this.registerWithBolt = shouldRegister;
                this.accountCheckbox = this.boltEmbedded.create(
                    'account_checkbox',
                    {
                        defaultValue: shouldRegister,
                        version: 'compact',
                        listeners: {
                            change: function (value) {
                                this.registerWithBolt = value;
                                checkoutData.setRegisterWithBolt(value);
                            }.bind(this)
                        },
                        language: this.locale
                    }
                );
                this.accountCheckbox.mount(this.registerWithBoltCheckboxSelector);
            } else {
                this.registerWithBolt = false;
            }
        },

        /**
         * Set initial checkout payment by bolt default payment data
         */
        setDefaultBoltPaymentData: function () {
            var address = null;

            if (this.savedCards().length > 0 && checkoutData.getIsUseExistingCard() !== 'no') {
                if (checkoutData.getSelectedBoltPaymentMethod() === null
                    || typeof checkoutData.getSelectedBoltPaymentMethod() === 'undefined'
                ) {
                    checkoutData.setSelectedBoltPaymentMethod(this.savedCards()[0]);
                }
                this.selectedCard(checkoutData.getSelectedBoltPaymentMethod());
                this.isUseExistingCard('yes');
                this.useSavedCard(true);
                checkoutData.setIsUseExistingCard('yes');
                address = checkoutData.getSelectedBoltPaymentMethod().billing_address;
            } else {
                address = checkoutData.getSelectedBoltAddress();
                this.isUseExistingCard('no');
                this.useSavedCard(false);
            }
            bolt.assignAddressData(address, 'billing');
        },

        /**
         * Toggle flag to handle new or existing cards
         *
         * @returns {boolean}
         */
        toggleUseSavedCard: function () {
            var address = null;

            $(this.creditCardFormSelector).empty();
            if ($(this.useSavedCardSelector).val() === 'yes') {
                this.useSavedCard(true);
                address = checkoutData.getSelectedBoltPaymentMethod().billing_address;
            } else {
                this.paymentField = this.boltEmbedded.create('payment_component', this.creditCardComponentConfig);
                this.paymentField.mount(this.creditCardFormSelector);
                this.useSavedCard(false);
                address = checkoutData.getSelectedBoltAddress();
            }
            bolt.assignAddressData(address, 'billing');
            this.isUseExistingCard($(this.useSavedCardSelector).val());
            checkoutData.setIsUseExistingCard($(this.useSavedCardSelector).val());
            return true;
        },

        /**
         * Binding click function for save credit card checkbox state
         */
        setIsSaveNewCard: function () {
            var self = this,
                checked = $(this.saveToBoltCheckboxSelector).prop('checked'),
                addNewCardContainterSelector = 'body';

            this.boltWalletError('');

            if (checked && !boltCustomer.hasWriteAccess()) {
                $(self.saveToBoltCheckboxSelector).prop('checked', false);
                $.when(reAuthenticate(addNewCardContainterSelector)).done(function (result) {
                    if (result) {
                        boltCustomer.hasWriteAccess(true);
                        self.isSaveNewCard(true);
                        checkoutData.setIsSaveNewCard(true);
                    } else {
                        checkoutData.setIsSaveNewCard(false);
                        self.isSaveNewCard(false);
                        $(self.saveToBoltCheckboxSelector).prop('checked', false);
                        self.boltWalletError(errors.boltWalletAuth);
                    }
                });
                return;
            }

            this.isSaveNewCard(checked);
            checkoutData.setIsSaveNewCard(checked);
        },

        /**
         * Success callback for transaction
         */
        onSuccess: function () {
            this.placeOrder();
        },

        /**
         * Error callback
         */
        onError: function () {
            var message = this.generalErrorMessage;

            this.messageContainer.addErrorMessage({
                message: message
            });
        },

        /**
         * Delegate bind function to handle existing card selection
         *
         * @param element
         * @param event
         */
        onCardChange: function (element, event) {
            var cardObj = null,
                cardId = $(event.target).find(':selected').val();

            if (!cardId || bolt.getBoltCards().length === 0) {
                return;
            }
            cardObj = bolt.getBoltCards().find(function (card) {
                return card.id === cardId;
            });

            this.selectedCard(cardObj);
            checkoutData.setSelectedBoltPaymentMethod(cardObj);
            bolt.assignAddressData(cardObj.billing_address, 'billing');
        },

        /**
         * Place order
         */
        placeOrderClick: function () {
            var useSavedCard = $(this.useSavedCardSelector).val() === 'yes';

            if (!useSavedCard) {
                loader.startLoader();
                this.paymentField.tokenize().then(function (data) {
                    this.selectedCard(data);
                    this.isCardNew = true;
                    this.placeOrder();
                }.bind(this)).catch(this.onError).finally(loader.stopLoader);
            } else {
                this.selectedCard().id = $(this.savedCardSelector).val();
                this.isCardNew = false;
                this.placeOrder();
            }
        },

        /**
         * @override
         * Override place order to unload unnecessary extension attributes for backend
         *
         * @returns {*}
         */
        placeOrder: function () {
            var placeOrderResult = false,
                boltBillingAddressId = bolt.getBillingAddressBoltId(),
                currentBillingAddress = _.clone(quote.billingAddress());

            // Clean up bolt information in address data
            if (boltBillingAddressId) {
                delete quote.billingAddress().extensionAttributes;
            }

            placeOrderResult = this._super();

            // Reset bolt information if place order was not successful
            if (!placeOrderResult && boltBillingAddressId) {
                bolt.assignAddressData(currentBillingAddress, 'billing');
            }

            return placeOrderResult;
        },

        /**
         * Get credit card label
         *
         * @param cardData
         */
        getCardLabel: function (cardData) {
            var expirationMonth = ('0' + cardData.expiration_month).slice(-2);

            return cardData.network.charAt(0).toUpperCase()
                + cardData.network.slice(1)
                + ' - '
                + $t('ending')
                + ' '
                + cardData.last4
                + ' '
                + $t('expires')
                + ' ('
                + expirationMonth
                + '/'
                + cardData.expiration_year
                + ')';
        }
    });
});
