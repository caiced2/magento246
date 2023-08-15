/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'ko',
    'mageUtils',
    'Magento_Checkout/js/view/shipping-address/list',
    'uiLayout',
    'Magento_QuickCheckout/js/model/customer/address-list',
    'Magento_QuickCheckout/js/model/account-handling',
    'Magento_QuickCheckout/js/model/customer/customer',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/checkout-data',
    'Magento_QuickCheckout/js/action/re-authenticate',
    'Magento_QuickCheckout/js/model/errors'
], function (
    $,
    _,
    ko,
    utils,
    ShippingAddressListComponent,
    layout,
    addressList,
    bolt,
    boltCustomer,
    formPopupState,
    checkoutData,
    reAuthenticate,
    errors
) {
    'use strict';

    var defaultRendererTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component: 'Magento_QuickCheckout/js/view/shipping-address/address-renderer/default',
        provider: 'checkoutProvider'
    };

    return ShippingAddressListComponent.extend({
        defaults: {
            boltWalletError: ko.observable(''),
            template: 'Magento_QuickCheckout/shipping-address/list',
            shippingFormTemplate: 'Magento_QuickCheckout/shipping-address/form',
            rendererTemplates: [],
            rendererComponents: [],
            shippingFormSelector: '#co-shipping-form',
            shippingStepSelector: '#checkout-step-shipping',
            saveAddressInBoltCheckboxSelector: '#shipping-save-to-bolt',
            saveAddressInMagentoCheckboxSelector: '#shipping-save-in-address-book',
            boltListVisible: ko.observable(addressList().length > 0),
            shouldSaveInBoltAddress: ko.observable(
                boltCustomer.getHasWriteAccess() && checkoutData.getIsSaveNewAddress()
            ),
            shouldSaveInAddressBook: ko.observable(function () {
                var formData = checkoutData.getShippingAddressFromData();

                if (typeof formData !== 'undefined'
                    && formData !== null
                    && typeof formData.saveInAddressBook !== 'undefined'
                ) {
                     return formData.saveInAddressBook;
                }
                return true;
            }())
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            var self = this;

            this._super();
            this.initChildren();

            addressList.subscribe(function (changes) {
                    changes.forEach(function (change) {
                        if (change.status === 'added') {
                            self.createRendererComponent(change.value, change.index);
                        }
                    });
                },
                this,
                'arrayChange'
            );

            boltCustomer.isBoltUser.subscribe(function () {
                if (boltCustomer.isBoltUser()) {
                    addressList(bolt.getBoltShippingAddresses());
                    if (addressList().length > 0) {
                        $(self.shippingFormSelector).remove();
                    }
                }
                self.boltListVisible(boltCustomer.isBoltUser() && addressList().length > 0);
            });

            boltCustomer.hasWriteAccess.subscribe(function () {
                self.shouldSaveInBoltAddress(boltCustomer.getHasWriteAccess() && checkoutData.getIsSaveNewAddress());
            });

            formPopupState.isVisible.subscribe(function (isVisible) {
                if (!isVisible) {
                    self.boltWalletError('');
                }
            });

            return this;
        },

        /**
         * @inheritdoc
         */
        initChildren: function () {
            _.each(addressList(), this.createRendererComponent, this);
            return this;
        },

        /**
         * Create new component that will render given address in the address list
         *
         * @param {Object} address
         * @param {*} index
         */
        createRendererComponent: function (address, index) {
            var rendererTemplate,
                templateData,
                rendererComponent;

            if (index in this.rendererComponents) {
                this.rendererComponents[index].address(address);
            } else {
                // rendererTemplates are provided via layout
                rendererTemplate = address.getType() != undefined && this.rendererTemplates[address.getType()] != undefined ? //eslint-disable-line
                    utils.extend({}, defaultRendererTemplate, this.rendererTemplates[address.getType()]) :
                    defaultRendererTemplate;
                templateData = {
                    parentName: this.name,
                    name: index
                };
                rendererComponent = utils.template(rendererTemplate, templateData);
                utils.extend(rendererComponent, {
                    address: ko.observable(address)
                });
                layout([rendererComponent]);
                this.rendererComponents[index] = rendererComponent;
            }
        },

        /**
         * Set state of address popup
         */
        openParentPopup: function () {
            formPopupState.isVisible(true);
        },

        /**
         * Save address in Bolt
         */
        saveAddressInBoltAddressBook: function () {
            var self = this,
                checked = $(this.saveAddressInBoltCheckboxSelector).prop('checked'),
                newAddressFormSelector = '#opc-new-shipping-address';

            if (checked && !boltCustomer.hasWriteAccess()) {
                $.when(reAuthenticate(newAddressFormSelector)).done(function (isAuthenticated) {
                    if (isAuthenticated) {
                        boltCustomer.hasWriteAccess(true);
                        self.shouldSaveInBoltAddress(true);
                        checkoutData.setIsSaveNewAddress(true);
                    } else {
                        self.shouldSaveInBoltAddress(false);
                        checkoutData.setIsSaveNewAddress(false);
                        $(self.saveAddressInBoltCheckboxSelector).prop('checked', false);
                        self.boltWalletError(errors.boltWalletAuth);
                    }
                });
                return;
            }

            this.shouldSaveInBoltAddress(checked);
            checkoutData.setIsSaveNewAddress(checked);

            return true;
        },

        /**
         * Save address in Magento account
         */
        saveAddressInAddressBook: function () {
            var checked = $(this.saveAddressInMagentoCheckboxSelector).prop('checked'),
                formData = checkoutData.getShippingAddressFromData() || {};

            this.shouldSaveInAddressBook(checked);
            formData['saveInAddressBook'] = checked;
            checkoutData.setShippingAddressFromData(formData);

            return true;
        },

        /**
         * Handle standard shipping forms after rendering
         */
        afterRender: function () {
            var self = this,
                observer = new MutationObserver(function () {
                    // Remove default Magento shipping address form on checkout reload if bolt user
                    if (boltCustomer.isBoltUser() && addressList().length > 0) {
                        $(self.shippingFormSelector).remove();
                    }
                });

            observer.disconnect();
            observer.observe($(self.shippingStepSelector).get(0), {
                attributes: true,
                subtree: true
            });
        }
    });
});
