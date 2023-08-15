/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'jquery-ui-modules/widget',
    'mage/translate'
], function ($, mageTemplate, alert) {
    'use strict';

    /**
     * Gift registry multi-ship address option
     */
    $.widget('mage.addressOption', {
        options: {
            addressOptionTmpl: '#address-option-tmpl' // 'Use gift registry shipping address' option.
        },

        /**
         * Add the gift registry shipping address option to every gift registry item on the multishipping page.
         * @private
         */
        _create: function () {
            this.addressOptionTmpl = mageTemplate(this.options.addressOptionTmpl);

            $.each(this.options.registryItems, $.proxy(this._addAddressOption, this));
        },

        /**
         * Add a 'Use gift registry shipping address' option to items on the multishipping page. Bind a change
         * handler on the quantity field of gift registry items to prevent changing the value.
         * @private
         * @param {Number} x - Index value from $.each() - Unused.
         * @param {Object} object - JSON Object - {"item": #, "address": #}
         */
        _addAddressOption: function (x, object) {
            var _this = this;

            this.element.find('select[id^="ship_"]').each(function (y, element) {
                var arr = $(element).attr('id').split('_'),
                    selectedIndices = _this.options.selectedAddressIndices,
                    selectOption;

                if (arr[2] && parseInt(arr[2], 10) === object.item) {
                    selectOption = _this.addressOptionTmpl({
                        data: {
                            _text_: $.mage.__('Use gift registry shipping address'),
                            _value_: _this.options.addressItemPrefix + object.address
                        }
                    });

                    selectOption = $(selectOption).appendTo(element);

                    if (selectedIndices.length > 0) {
                        _this._setSelected(selectOption, parseInt(arr[1], 10), selectedIndices);
                    }

                    $(element).closest('tr').find('input[type="text"]').on('focus', function (event) {
                        $(event.target).trigger('blur');
                        alert({
                            content: $.mage.__('The only place to change the number of gift registry items is on the Gift Registry Info page or directly in your cart.') //eslint-disable-line max-len
                        });
                    });
                }
            });
        },

        /**
         * Search for the target index and set the selected attribute when there is a match. This will mark
         * the 'Use gift registry shipping address' option as 'selected'.
         * @private
         * @param {Object} option - Select option object.
         * @param {Number} index - The target index value being searched for.
         * @param {Array} indices - An array of indices to iterate through looking for the target.
         */
        _setSelected: function (option, index, indices) {
            var i;

            for (i = 0; i < indices.length; i++) {
                if (indices[i] === index) {
                    option.prop('selected', true);
                    break;
                }
            }
        }
    });

    return $.mage.addressOption;
});
