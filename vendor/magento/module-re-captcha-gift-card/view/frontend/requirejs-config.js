/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// eslint-disable-next-line no-unused-vars
var config = {
    config: {
        mixins: {
            'Magento_GiftCardAccount/js/action/set-gift-card-information': {
                'Magento_ReCaptchaGiftCard/js/action/set-gift-card-information-mixin': true
            },
            'Magento_GiftCardAccount/js/action/get-gift-card-information': {
                'Magento_ReCaptchaGiftCard/js/action/get-gift-card-information-mixin': true
            }
        }
    }
};
