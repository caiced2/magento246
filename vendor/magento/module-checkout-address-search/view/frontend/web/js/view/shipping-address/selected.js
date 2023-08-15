/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/model/quote'
], function (Component, $t, customerData, formPopUpState, quote) {
    'use strict';

    var countryData = customerData.get('directory-data');

    return Component.extend({
        defaults: {
            template: 'Magento_CheckoutAddressSearch/shipping-address/selected',
            selectShippingAddressProvider: '',
            defaultAddress: quote.shippingAddress(),
            isChangeAddressVisible: true,
            modules: {
                selectionModal: '${ $.selectShippingAddressProvider }'
            },
            noAddressMessage: $t('No address selected')
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('defaultAddress');
            quote.shippingAddress.subscribe(this.onShippingAddressUpdate, this);

            return this;
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
         * Edit address.
         */
        editAddress: function () {
            formPopUpState.isVisible(true);
        },

        /**
         * Open address selection modal.
         */
        openAddressSelection: function () {
            this.selectionModal().openModal();
        },

        /**
         * On shipping address update.
         *
         * @param {Object} address
         */
        onShippingAddressUpdate: function (address) {
            this.defaultAddress(address);
        }
    });
});
