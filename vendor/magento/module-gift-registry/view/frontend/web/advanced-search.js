/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mage.advancedSearch', {
        options: {
            ajaxSpinnerSelector: '#gr-please-wait',
            ajaxResultSelector: '#gr-type-specific-options',
            ajaxResultContainerSelector: '#gr-type-specific-fieldset'
        },

        /**
         * bind handlers
         * @private
         */
        _create: function () {
            this.element.on('change', $.proxy(this._ajaxUpdate, this));
            this.options.selectedOption && this.element.val(this.options.selectedOption).trigger('change');
        },

        /**
         * ajax call for search option list
         * @private
         */
        _ajaxUpdate: function () {
            var typeId = this.element.val();

            $(this.options.ajaxSpinnerSelector).show();

            $.post(this.options.url, {
                'type_id': typeId
            }, $.proxy(function (data) {
                $(this.options.ajaxSpinnerSelector).hide();
                $(this.options.ajaxResultSelector).html(data);
                $(this.options.ajaxResultContainerSelector).toggle(!!typeId);
                $('body').trigger('contentUpdated');
            }, this));
        }
    });

    return $.mage.advancedSearch;
});
