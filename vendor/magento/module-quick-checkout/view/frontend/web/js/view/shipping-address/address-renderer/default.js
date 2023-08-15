/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/shipping-address/address-renderer/default',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/checkout-data',
    'Magento_QuickCheckout/js/model/account-handling'
], function (
    $,
    ko,
    DefaultAddressRenderingComponent,
    selectShippingAddressAction,
    quote,
    formPopUpState,
    checkoutData,
    bolt
) {
    'use strict';

    return DefaultAddressRenderingComponent.extend({
        defaults: {
            template: 'Magento_QuickCheckout/shipping-address/address-renderer/default',
            newAddressDialogSelector: '[data-open-modal="opc-new-shipping-address"]'
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super();
            this.isSelected = ko.computed(function () {
                var isSelected = false;

                // Handle checkout reload
                if (bolt.isBoltUser()) {
                    isSelected = checkoutData.getSelectedShippingAddress() === this.address().getKey();
                    if (isSelected) {
                        this.selectAddress();
                    }
                }
                return isSelected;
            }, this);

            return this;
        },

        /**
         * Show popup
         */
        showPopup: function () {
            $(this.newAddressDialogSelector).trigger('click');
        }
    });
});
