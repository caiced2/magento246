/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
        'mage/translate',
        'mage/storage',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_QuickCheckout/js/model/account-handling',
        'Magento_QuickCheckout/js/action/select-cheapest-shipping-rate',
        'Magento_QuickCheckout/js/action/navigate-to-payment',
        'Magento_QuickCheckout/js/model/customer/address'
    ], function (
        $t,
        storage,
        quote,
        messageList,
        checkoutData,
        urlBuilder,
        fullScreenLoader,
        bolt,
        selectCheapestShippingAction,
        navigateToPaymentAction,
        boltAddress
    ) {
        'use strict';

        return function (response) {
            var defaultAddressChangedMessage = $t('The default shipping address is invalid. A different address from the address book has been selected.'), // eslint-disable-line max-len
                defaultNoValidAddressMessage = $t('You don\'t have any valid addresses in the Bolt wallet. Enter a valid shipping address below.'); // eslint-disable-line max-len

            bolt.clearBoltCheckoutData();
            response.payment_methods.forEach(function (paymentMethod, index) {
                response.payment_methods[index].billing_address = boltAddress(paymentMethod.billing_address);
            });
            checkoutData.setBoltAccountDetails(response);
            checkoutData.setSelectedPaymentMethod('quick_checkout');
            if (response.default_address_changed && response.addresses.length) {
                messageList.addSuccessMessage({message: defaultAddressChangedMessage});
            } else if (response.addresses.length === 0) {
                messageList.addSuccessMessage({message: defaultNoValidAddressMessage});
            }
            if (response.addresses && 0 in response.addresses && !quote.isVirtual()) {
                bolt.assignAddressData(boltAddress(response.addresses[0]), 'shipping');
                if (!response.default_address_changed) {
                    selectCheapestShippingAction().then(function () {
                        navigateToPaymentAction();
                    }).catch(function () {
                        bolt.setBoltUser(true);
                    });
                } else {
                    bolt.setBoltUser(true);
                }
            } else {
                bolt.setBoltUser(true);
            }
            checkoutData.setAccountInformationLoaded(true);
        };
    }
);
