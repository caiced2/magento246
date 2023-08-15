define(['jquery'], function ($) {
    'use strict';

    return function (Component) {
        return Component.extend({
            defaults: {
                productGiftAmountSelector: '#giftcard-amount',
                productGiftAmountInput: '#giftcard-amount-input'
            },

            /**
             * Initialize
             *
             */
            initialize: function () {

                this._super();

                /**
                 * Gift Type product open amount message for pay later banner
                 */
                $(this.productGiftAmountSelector).on('change', this._onGiftPriceChange.bind(this));

                /**
                 * Gift Type product open amount message for pay later banner
                 */
                $(this.productGiftAmountInput).on('change', this._onGiftPriceChange.bind(this));
            },

            /**
             * Handle update product gift type price
             */
            _onGiftPriceChange: function (event) {
                this.price = $(event.target).val();
                this._updateAmount();
            }
        });
    };

});
