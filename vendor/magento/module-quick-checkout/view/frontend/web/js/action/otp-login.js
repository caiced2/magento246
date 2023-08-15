/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
        'mage/translate',
        'mage/storage',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList',
        'Magento_Customer/js/customer-data',
        'Magento_QuickCheckout/js/action/get-account-details',
        'Magento_QuickCheckout/js/model/customer/customer'
    ], function (
        $t,
        storage,
        quote,
        fullScreenLoader,
        globalMessageList,
        customerData,
        getAccountDetails,
        boltCustomer
    ) {
        'use strict';

        return function (code, isAuto) {
            var isAutoLogin = isAuto !== undefined ? isAuto : false,
                payload = {code: code, isAutoLogin: isAutoLogin},
                defaultError = $t('Could not authenticate. Please try again later.');

            fullScreenLoader.startLoader();

            return storage.post(
                'quick-checkout/ajax/login',
                JSON.stringify(payload),
                false
            ).done(function (response) {
                var accountDetails;

                if (!response.success) {
                    globalMessageList.addErrorMessage({'message': response.message});
                    return;
                }

                boltCustomer.setHasWriteAccess(response.hasWriteAccess);
                if (!response.isLoggedInBothNetworks) {
                    accountDetails = getAccountDetails();
                    accountDetails.done(
                        function () {
                            var email = boltCustomer.getEmail();

                            if (email !== '') {
                                quote.guestEmail = email;
                            }
                        }
                    );
                    return;
                }

                customerData.invalidate(['customer']);
                window.location.reload();
            }).fail(function () {
                globalMessageList.addErrorMessage({'message': defaultError});
            }).always(function () {
                fullScreenLoader.stopLoader();
            });
        };
    }
);
