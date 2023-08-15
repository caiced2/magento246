/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'jquery',
    'mage/translate',
    'Magento_Checkout/js/view/billing-address',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/checkout-data',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Customer/js/model/address-list',
    'Magento_Ui/js/lib/view/utils/async'
], function (_, $, $t, Component, quote, selectBillingAddress, checkoutData, modal, createBillingAddress, addressList) {
    'use strict';

    var lastSelectedBillingAddress = null;

    return Component.extend({
        defaults: {
            template: 'Magento_CheckoutAddressSearch/billing-address',
            detailsTemplate: 'Magento_CheckoutAddressSearch/billing-address/details',
            selectBillingAddressProvider: '',
            isNewAddressAdded: false,
            newCustomerBillingAddress: null,
            popUpButtons: {
                save: {
                    text: $t('Save Address'),
                    class: 'action primary action-save-address'
                },
                cancel: {
                    text: $t('Cancel'),
                    class: 'action secondary action-hide-popup'
                }
            },
            popUpForm: {
                element: '.new-billing-address-form',
                options: {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title: $t('Billing Address')
                }
            },
            modules: {
                selectBillingAddress: '${ $.selectBillingAddressProvider }'
            },
            noAddressMessage: $t('No address selected')
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.isNewAddressAdded(!!checkoutData.getNewCustomerBillingAddress());

            if (this.isNewAddressAdded()) {
                this.newCustomerBillingAddress(createBillingAddress(checkoutData.getNewCustomerBillingAddress()));
            }

            quote.billingAddress.subscribe(function (addressOption) {
                if (!addressOption) {
                    _.each(addressList(), function (address) {
                        if (address.isDefaultBilling && address.isDefaultBilling()) {
                            selectBillingAddress(address);
                            checkoutData.setSelectedBillingAddress(address.getKey());
                        }
                    }, this);
                }
            }, this);

            $.async({
                component: this,
                selector: this.popUpForm.element
            }, this.initPopup.bind(this));

            return this;
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('isNewAddressAdded newCustomerBillingAddress');

            quote.billingAddress.subscribe(function () {
                this.isAddressFormVisible(false);
            }, this);

            return this;
        },

        /** @inheritdoc */
        useShippingAddress: function () {
            if (this.isAddressSameAsShipping()) {
                lastSelectedBillingAddress = quote.billingAddress();
                selectBillingAddress(quote.shippingAddress());
                checkoutData.setSelectedBillingAddress(quote.shippingAddress().getKey());
                this.updateAddresses();
            } else if (lastSelectedBillingAddress &&
                lastSelectedBillingAddress.getCacheKey() !== quote.billingAddress().getCacheKey()
            ) {
                selectBillingAddress(lastSelectedBillingAddress);
                checkoutData.setSelectedBillingAddress(lastSelectedBillingAddress.getKey());
                this.updateAddresses();
            }

            return true;
        },

        /**
         * Edit address action.
         */
        editAddress: function () {
            this.showFormPopUp();
        },

        /**
         * Cancel address edit action.
         */
        cancelAddressEdit: function () {
            checkoutData.setBillingAddressFromData($.extend(true, {}, this.temporaryAddress));
            this.closeFormPopUp();
        },

        /**
         * Init form for billing address.
         *
         * @param {HTMLElement} element
         */
        initPopup: function (element) {
            this.popUpForm.options.buttons = [
                {
                    text: this.popUpButtons.save.text,
                    class: this.popUpButtons.save.class,
                    click: function () {
                        this.updateAddress();

                        if (!this.source.get('params.invalid')) {
                            this.isNewAddressAdded(true);
                            this.closeFormPopUp();
                        }
                    }.bind(this)
                },
                {
                    text: this.popUpButtons.cancel.text,
                    class: this.popUpButtons.cancel.class,
                    click: this.cancelAddressEdit.bind(this)
                }
            ];

            this.popUpForm.options.modalCloseBtnHandler = this.cancelAddressEdit.bind(this);
            this.popUpForm.options.keyEventHandlers = {
                escapeKey: this.cancelAddressEdit.bind(this)
            };

            /** @inheritdoc */
            this.popUpForm.options.opened = function () {
                // Store temporary address for revert action in case when user click cancel action
                this.temporaryAddress = $.extend(true, {}, checkoutData.getBillingAddressFromData());
            }.bind(this);

            this.popUp = modal(this.popUpForm.options, $(element));
        },

        /**
         * Show from popUp.
         */
        showFormPopUp: function () {
            this.isAddressFormVisible(true);
            this.popUp.openModal();
        },

        /**
         * Close from popUp.
         */
        closeFormPopUp: function () {
            this.isAddressFormVisible(false);
            this.popUp.closeModal();
        },

        /**
         * Open address selection modal.
         */
        openAddressSelection: function () {
            this.selectBillingAddress().openModal();
        },

        /** @inheritdoc */
        updateAddress: function () {
            this._super();

            if (!this.selectedAddress() || this.isAddressFormVisible()) {
                this.newCustomerBillingAddress(createBillingAddress(checkoutData.getNewCustomerBillingAddress()));
            }
        },

        /**
         * Is address editable.
         *
         * @param {Object} address
         * @returns {Boolean}
         */
        isAddressEditable: function (address) {
            if (address) {
                return address.getType() === 'new-customer-billing-address';
            }
        }
    });
});
