/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Customer/js/customer-data'
], function (customerData) {
    'use strict';

    var countryData = customerData.get('directory-data');

    /**
     * Returns new address object
     *
     * @param {Object} addressData
     * @return {Object}
     */
    return function (addressData) {
        if (typeof addressData.region_id === 'undefined') {
            addressData.region_id = null;
        }
        if (typeof addressData.region_code === 'undefined') {
            addressData.region_code = null;
        }

        return {
            customerAddressId: addressData.external_address ? null : addressData.id,
            email: addressData.email,
            countryId: addressData.country_id,
            regionId: addressData.region_id,
            regionCode: addressData.region_code,
            region: addressData.region,
            street: addressData.street,
            telephone: addressData.telephone,
            postcode: addressData.postcode,
            city: addressData.city,
            firstname: addressData.firstname,
            lastname: addressData.lastname,
            company: addressData.company,
            sameAsBilling: 0,
            saveInAddressBook: addressData.save_in_address_book,
            extensionAttributes: {boltId: addressData.external_address ? addressData.id : null},

            /**
             * @return {*}
             */
            isDefaultShipping: function () {
                return false;
            },

            /**
             * @return {*}
             */
            isDefaultBilling: function () {
                return false;
            },

            /**
             * @return {*}
             */
            getAddressInline: function () {
                var countryName = countryData()[this.countryId] !== undefined ? countryData()[this.countryId].name : '';

                return this.firstname + ' ' + this.lastname + ', ' + this.street.join(', ') + ', ' +
                    this.city + ', ' + this.postcode + ', ' + countryName;
            },

            /**
             * @return {String}
             */
            getType: function () {
                return 'bolt-customer-address';
            },

            /**
             * @return {String}
             */
            getKey: function () {
                if (!this.extensionAttributes.boltId) {
                    return this.getType() + this.customerAddressId;
                }
                return this.getType() + this.extensionAttributes.boltId;
            },

            /**
             * @return {String}
             */
            getCacheKey: function () {
                return this.getKey();
            },

            /**
             * @return {Boolean}
             */
            isEditable: function () {
                return false;
            },

            /**
             * @return {Boolean}
             */
            canUseForBilling: function () {
                return true;
            }
        };
    };
});
