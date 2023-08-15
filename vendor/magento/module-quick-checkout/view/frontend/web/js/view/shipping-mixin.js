/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'mage/translate',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_QuickCheckout/js/action/create-shipping-address',
    'Magento_QuickCheckout/js/model/account-handling',
    'Magento_QuickCheckout/js/model/customer/address-list',
    'Magento_Checkout/js/model/address-converter'
], function (
    $,
    _,
    $t,
    quote,
    customer,
    checkoutData,
    selectShippingAddress,
    createShippingAddress,
    bolt,
    addressList,
    addressConverter
) {
    'use strict';

    return function (Shipping) {
        return Shipping.extend({

            defaults: {
                shippingMethodErrorMessage:
                    $t('The shipping method is missing. Select the shipping method and try again.'),
                loginFormSelector: 'form[data-role=email-with-possible-login]',
                loginFormEmailSelector: 'form[data-role=email-with-possible-login] input[name=username]'
            },

            /**
             * @return {exports}
             */
            initialize: function () {
                var parent = this._super(),
                    hasNewAddress = true;

                if (bolt.isBoltUser()) {
                    hasNewAddress = addressList.some(function (address) {
                        return address.getType() === 'new-customer-address';
                    });
                    this.isNewAddressAdded(hasNewAddress);
                }

                return parent;
            },

            /**
             * @return {Boolean}
             */
            validateShippingInformation: function () {
                var method = quote.shippingMethod();

                this.source.set('params.invalid', false);

                // Validate method
                if (!method) {
                    this.errorValidationMessage(this.shippingMethodErrorMessage);
                    return false;
                }
                // Validation of future Bolt data and forms
                if (bolt.isBoltUser()) {
                    return this.validateInlineFormForBoltUsers();
                }
                return this._super();
            },

            /**
             * Save new shipping address
             */
            saveNewAddress: function () {
                var addressData,
                    newShippingAddress;

                if (bolt.isBoltUser()) {
                    this.source.set('params.invalid', false);
                    this.triggerShippingDataValidateEvent();

                    if (!this.source.get('params.invalid')) {
                        addressData = this.source.get('shippingAddress');
                        addressData['save_in_address_book'] = this.saveInAddressBook ? 1 : 0;
                        newShippingAddress = createShippingAddress(addressData);
                        selectShippingAddress(newShippingAddress);
                        checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                        checkoutData.setNewCustomerShippingAddress($.extend(true, {}, addressData));
                        this.getPopUp().closeModal();
                        this.isNewAddressAdded(true);
                    }
                } else {
                    this._super();
                }
            },

            /**
             * Validates the inline address form for Bolt user
             * is needed when a Bolt user has no address or all addresses are invalid
             * @returns {boolean}
             */
            validateInlineFormForBoltUsers: function () {
                var shippingAddress,
                    addressData,
                    field;

                if (addressList().length <= 0 && this.isFormInline) {
                    this.source.set('params.invalid', false);
                    this.triggerShippingDataValidateEvent();

                    if (!quote.shippingMethod()['method_code']) {
                        this.errorValidationMessage(
                            $t('The shipping method is missing. Select the shipping method and try again.')
                        );
                    }

                    if (this.source.get('params.invalid') ||
                        !quote.shippingMethod()['method_code'] ||
                        !quote.shippingMethod()['carrier_code']
                    ) {
                        this.focusInvalid();
                        return false;
                    }

                    shippingAddress = quote.shippingAddress();
                    addressData = addressConverter.formAddressDataToQuoteAddress(
                        this.source.get('shippingAddress')
                    );

                    //Copy form data to quote shipping address object
                    for (field in addressData) {
                        if (addressData.hasOwnProperty(field) &&  //eslint-disable-line max-depth
                            shippingAddress.hasOwnProperty(field) &&
                            typeof addressData[field] != 'function' &&
                            _.isEqual(shippingAddress[field], addressData[field])
                        ) {
                            shippingAddress[field] = addressData[field];
                        } else if (typeof addressData[field] != 'function' &&
                            !_.isEqual(shippingAddress[field], addressData[field])) {
                            shippingAddress = addressData;
                            break;
                        }
                    }
                    selectShippingAddress(shippingAddress);
                }
                return true;
            }
        });
    };
});
