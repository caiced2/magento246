/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Theme/js/row-builder',
    'jquery-ui-modules/widget'
], function ($, rowBuilder) {
    'use strict';

    /**
     * Extending the rowBuilder widget and adding custom formProcessing for rendering recipients
     */
    $.widget('mage.giftRegistry', rowBuilder, {

        options: {
            rowIdPrefix: 'registrant:',
            rowCustomIdPrefix: 'registrant:custom:',
            addrTypeSelector: '#address_type_or_id',
            newShipAddrFormSelector: '#shipping-new-address-form',
            shipAddrDataSelector: 'div[data-shipping-address]',
            shipAddrDataAttr: 'shipping-address',
            newAddrTypeVal: 'new'
        },

        /**
         *
         * @private
         */
        _create: function () {
            this._super();

            if ($(this.options.shipAddrDataSelector).data(this.options.shipAddrDataAttr)) {
                $(this.options.addrTypeSelector).val(this.options.newAddrTypeVal);
                $(this.options.newShipAddrFormSelector).show();
            }
            this.element.on('change', this.options.addrTypeSelector, $.proxy(this._handleShipAddrChange, this));
        },

        /**
         * @override
         * Process and loop through all row data to create preselected values. This is used for any error on submit.
         * For complex implementations the inheriting widget can override this behavior
         * @public
         * @param {Object} formDataArr
         */
        processFormDataArr: function (formDataArr) {
            var formData = formDataArr.formData,
                i;

            for (i = this.options.rowIndex = 0; i < formData.length; this.options.rowIndex = i++) {
                this.addRow(i);
                this._processFormDataArrKey(i, formData[i], false);
            }

        },

        /**
         * Function to recursively process json encoded form data
         * This utility helps in processing formData values which are objects/json themselves
         * @private
         * @param {Number} index - index of the curr row
         * @param {Object} formRow - row object containing field name and value
         * @param {Boolean} isCustom - if the registrant has custom fields
         *
         */
        _processFormDataArrKey: function (index, formRow, isCustom) {
            var idPrefix = isCustom ? this.options.rowCustomIdPrefix : this.options.rowIdPrefix,
                key;

            for (key in formRow) {
                if (formRow.hasOwnProperty(key)) {
                    if (Object.prototype.toString.call(formRow[key]) === '[object Object]') { //eslint-disable-line
                        this._processFormDataArrKey(index, formRow[key], true);
                    } else {
                        this.setFieldById(idPrefix + key + index, formRow[key]);
                    }
                }
            }
        },

        /**
         * Function to handle shipping address change
         * @private
         * @param {Object} e - native event object
         */
        _handleShipAddrChange: function (e) {
            $(this.options.newShipAddrFormSelector).toggle(e.target.value === this.options.newAddrTypeVal);
        }
    });

    return $.mage.giftRegistry;
});
