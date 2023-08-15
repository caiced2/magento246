/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/element/ui-select',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/model/customer/address',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote'
], function (_, Select, selectBillingAddress, checkoutData, Address, customerData, quote) {
    'use strict';

    var countryData = customerData.get('directory-data');

    return Select.extend({
        defaults: {
            addressTmpl: 'Magento_CheckoutAddressSearch/billing-address/address-renderer/item',
            modal: '${$.parentName}',
            quantityPlaceholder: 'addresses',
            value: {},
            billingAddressProvider: '',
            isNewAddressAdded: false,
            imports: {
                isNewAddressAdded: '${ $.billingAddressProvider }:isNewAddressAdded',
                newCustomerBillingAddress: '${ $.billingAddressProvider }:newCustomerBillingAddress'
            },
            listens: {
                newCustomerBillingAddress: 'onChangeNewCustomerBillingAddress'
            },
            modules: {
                modal: '${ $.modal }',
                billingAddress: '${ $.billingAddressProvider }'
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

            if (this.newCustomerBillingAddress) {
                existingOptions.push(this.newCustomerBillingAddress);
            }

            _.each(this.options, function (option) {
                existingOptions.push(new Address(option));
            });

            this.options = this.sortAddresses(existingOptions);

            return this;
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('isNewAddressAdded');

            quote.billingAddress.subscribe(function (address) {
                if (address) {
                    this.value(address);
                }
            }, this);

            return this;
        },

        /**
         * Set selected customer billing address.
         *
         * @param {Object} option
         */
        selectBillingAddress: function (option) {
            delete option.level;
            delete option.isVisited;
            delete option.path;

            this.value(option);
            this.modal().closeModal();
            selectBillingAddress(option);
            checkoutData.setSelectedBillingAddress(option.getKey());
            this.billingAddress().updateAddresses(true);
        },

        /**
         * Edit address
         */
        editAction: function () {
            this.modal().closeModal();
            this.billingAddress().editAddress();
        },

        /** @inheritdoc */
        success: function (response) {
            var existingOptions = this.options();

            _.each(response.options, function (opt) {
                var address = new Address(opt);

                // add options only if they were not added yet
                if (!_.findWhere(existingOptions, {
                    customerAddressId: address.id
                })) {
                    existingOptions.push(address);
                }
            });

            this.total = response.total;
            this.newAddressInSearchResult = false;

            if (this.newCustomerBillingAddress) {
                existingOptions.push(this.newCustomerBillingAddress);
            }

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
                defaultAddress;

            if (!_.isEmpty(this.newCustomerBillingAddress) &&
                this.canShowNewAddress(this.newCustomerBillingAddress, this.currentSearchKey)
            ) {
                if (this.newAddressInSearchResult === false) {
                    this._setItemsQuantity(++this.total);
                }
                this.newAddressInSearchResult = true;
                resultOptions.push(this.newCustomerBillingAddress);
            }

            defaultAddress = _.find(existingOptions, function (address) {
                return address.isDefaultShipping();
            });

            !_.isEmpty(defaultAddress) && resultOptions.push(defaultAddress);

            return resultOptions.concat(_.filter(existingOptions, function (address) {
                return address.getType() !== 'new-customer-billing-address' && !address.isDefaultShipping();
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
         * Is address editable.
         *
         * @param {Object} address
         * @returns {Boolean}
         */
        isAddressEditable: function (address) {
            return address.getType() === 'new-customer-billing-address';
        },

        /**
         * On new customer billing address change.
         *
         * @param {Object} address
         */
        onChangeNewCustomerBillingAddress: function (address) {
            var options = _.filter(this.options(), function (option) {
                return option.getType() !== 'new-customer-billing-address';
            });

            options.push(address);
            this.options(this.sortAddresses(options));
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
