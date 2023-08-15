/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            toggleGiftCard: 'Magento_GiftCard/toggle-gift-card'
        }
    },
    'config': {
        'mixins': {
            'Magento_Paypal/js/view/amountProviders/product': {
                'Magento_GiftCard/product-mixin': true
            }
        }
    }
};
