/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mage.toggleGiftCard', {
        options: {
            amountSelector: '#giftcard-amount-input',
            amountBoxSelector: '#giftcard-amount-box',
            amountLabelSelector: null,
            amountLabelDropDownSelector: null
        },

        /**
         * Bind handlers to events
         */
        _create: function () {
            this.element.on('change', $.proxy(this._toggleGiftCard, this))
                .trigger('change');
        },

        /**
         * Toggle gift card
         * @private
         */
        _toggleGiftCard: function () {
            var jQueryObjects = $(this.options.amountSelector)
                .add(this.options.amountBoxSelector)
                .add(this.options.amountLabelSelector);

            if (this.element.val() === 'custom') {
                jQueryObjects.show();
                $(this.options.amountLabelDropDownSelector).hide();
            } else {
                jQueryObjects.hide();
                $(this.options.amountLabelDropDownSelector).show();
            }
        }
    });

    return $.mage.toggleGiftCard;
});
