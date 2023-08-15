/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/element/ui-select',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/model/customer/address',
    'Magento_Customer/js/model/address-list',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-address/form-popup-state'
], function (
    _,
    Select,
    selectShippingAddressAction,
    checkoutData,
    Address,
    addressList,
    customerData,
    quote,
    formPopUpState
) {
    'use strict';

    var countryData = customerData.get('directory-data');

    return Select.extend({
        defaults: {
            addressTmpl: 'Magento_CheckoutAddressSearch/shipping-address/address-renderer/item',
            modalProvider: '${ $.parentName }',
            quantityPlaceholder: 'addresses',
            modules: {
                modal: '${ $.modalProvider }'
            },
            newAddressInSearchResult: false
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();

            this._setItemsQuantity(this.total);
        },

        /** @inheritdoc */
        initConfig: function () {
            var existingOptions = [];

            this._super();

            _.each(this.options, function (option) {
                existingOptions.push(new Address(option));
            });

            this.options = this.sortAddresses(existingOptions);

            return this;
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super();

            this.onShippingAddressChange(quote.shippingAddress());
            quote.shippingAddress.subscribe(this.onShippingAddressChange, this);

            addressList.subscribe(function (changes) {
                _.each(changes, function (change) {

                    if (change.status === 'added' && change.value.getType() === 'new-customer-address') {
                        this.options().push(change.value);
                        this.options(this.sortAddresses(this.options()));
                    }
                }, this);
            }, this, 'arrayChange');

            return this;
        },

        /**
         * On shipping address change.
         *
         * @param {Object} address
         */
        onShippingAddressChange: function (address) {
            if (address) {
                this.options(this.sortAddresses(this.options()));
            }
            this.value(address);
        },

        /**
         * Set selected customer shipping address.
         *
         * @param {Object} option
         */
        selectShippingAddress: function (option) {
            delete option.level;
            delete option.isVisited;
            delete option.path;

            this.value(option);
            this.modal().closeModal();
            selectShippingAddressAction(option);
            checkoutData.setSelectedShippingAddress(option.getKey());
        },

        /**
         * Edit address.
         */
        editAction: function () {
            this.modal().closeModal();
            formPopUpState.isVisible(true);
        },

        /** @inheritdoc */
        success: function (response) {
            var existingOptions = this.options();

            // eliminate duplicates from ui-select search result
            _.each(response.options, function (opt) {
                if (!_.findWhere(existingOptions, {
                    customerAddressId: opt.id
                })) {
                    existingOptions.push(new Address(opt));
                }
            });

            this.total = response.total;
            this.newAddressInSearchResult = false;

            this.options(this.sortAddresses(existingOptions));
        },

        /**
         * Sort addresses.
         *
         * Sort addresses in such way that new address and default one are displayed on the top of results
         * if one of them matches search criteria or search is empty.
         *
         * @param {Array} existingOptions
         * @return {Array}
         */
        sortAddresses: function (existingOptions) {

            var resultOptions = [],
                defaultAddress, newAddress;

            newAddress = _.find(addressList(), function (address) {
                return address.getType() === 'new-customer-address';
            });

            if (!_.isEmpty(newAddress) && this.canShowNewAddress(newAddress, this.currentSearchKey)) {
                if (this.newAddressInSearchResult === false) {
                    this._setItemsQuantity(++this.total);
                }
                this.newAddressInSearchResult = true;
                resultOptions.push(newAddress);
            }

            defaultAddress = _.find(existingOptions, function (address) {
                return address.isDefaultShipping();
            });

            !_.isEmpty(defaultAddress) && resultOptions.push(defaultAddress);

            return resultOptions.concat(_.filter(existingOptions, function (address) {
                return address.getType() !== 'new-customer-address' && !address.isDefaultShipping();
            }));
        },

        /**
         * Check if we can show address.
         *
         * @param {Object} address
         * @param {String} searchKey
         * @return {Boolean}
         */
        canShowNewAddress: function (address, searchKey) {
            var searchFields = ['city', 'postcode', 'region', ['street']],
                filteredResults = [],
                value = '';

            if (!searchKey) {
                return true;
            }

            filteredResults = searchFields.filter(function (elem) {
                value = address[elem];

                if (_.isArray(value)) {
                    value = value.join(' ');
                }

                return value.toLowerCase().indexOf(searchKey.toLowerCase()) !== -1;
            });

            return !!filteredResults.length;
        },

        /**
         * Get country name.
         *
         * @param {String} countryId
         * @return {String}
         */
        getCountryName: function (countryId) {
            return countryData()[countryId] !== undefined ? countryData()[countryId].name : '';
        },

        /**
         * Is address selected.
         *
         * @param {Object} address
         * @returns {Boolean}
         */
        isAddressSelected: function (address) {
            return !_.isEmpty(this.value()) && this.value().getKey() === address.getKey();
        },

        /**
         * Return formatted items placeholder.
         *
         * @param {Object} data - option data
         * @returns {String}
         */
        getItemsPlaceholder: function (data) {
            var prefix = '';

            if (this.lastSearchKey) {
                prefix = 'Found ';
            }

            return prefix + data + ' ' + this.quantityPlaceholder;
        },

        /** @inheritdoc */
        onScrollDown: function (data, event) {
            if (this.loading() || this.options().length >= this.total) {
                return;
            }

            this._super(data, event);
        }
    });
});
