/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mage.fileElement', {
        options: {
            hiddenFieldSuffix: '_value',
            delegationSelector: '.field input[type="file"]'
        },

        /**
         * Bind handler to change event. On change, set the value of the hidden input field to be
         * the same as the value of the file input field.
         * @private
         */
        _create: function () {
            this.element.on('change', this.options.delegationSelector, $.proxy(function (e) {
                var input = $(e.target);

                $(this._esc('#' + input.attr('id') + this.options.hiddenFieldSuffix)).val(input.val());
            }, this));
        },

        /**
         * Utility function to add escape characters for jQuery selector strings.
         * @private
         * @param {String} str - Selector string to be processed.
         * @return {String}
         */
        _esc: function (str) {
            return str ? str.replace(/([ ;&,.+*~\':"!\^$\[\]()=>|\/@])/g, '\\$1') : str;
        }
    });

    return $.mage.fileElement;
});
