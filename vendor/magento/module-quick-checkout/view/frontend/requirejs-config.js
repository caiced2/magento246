/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
var quickCheckoutMixinEnabled = !window.quickCheckoutDisabled,
    config,
    billingAddressForm = 'Magento_QuickCheckout/template/billing-address/form.html';

config = {
   map: {
        '*': {
            'Magento_Checkout/template/billing-address/form.html': billingAddressForm
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/form/element/email': {
                'Magento_QuickCheckout/js/view/form/email-mixin': quickCheckoutMixinEnabled
            },
            'Magento_Checkout/js/view/shipping': {
                'Magento_QuickCheckout/js/view/shipping-mixin': quickCheckoutMixinEnabled
            },
            'Magento_Checkout/js/checkout-data': {
                'Magento_QuickCheckout/js/checkout-data-mixin': quickCheckoutMixinEnabled
            },
            'Magento_Checkout/js/model/checkout-data-resolver': {
                'Magento_QuickCheckout/js/model/checkout-data-resolver-mixin': quickCheckoutMixinEnabled
            },
            'Magento_Checkout/js/view/billing-address/list': {
                'Magento_QuickCheckout/js/view/billing-address/list-mixin': quickCheckoutMixinEnabled
            },
            'Magento_Checkout/js/action/set-shipping-information': {
                'Magento_QuickCheckout/js/action/set-shipping-information-mixin': quickCheckoutMixinEnabled
            },
            'Magento_Checkout/js/action/select-payment-method': {
                'Magento_QuickCheckout/js/action/select-payment-method-mixin': quickCheckoutMixinEnabled
            },
            'Magento_Checkout/js/action/place-order': {
                'Magento_QuickCheckout/js/action/place-order-mixin': quickCheckoutMixinEnabled
            },
            'Magento_Checkout/js/model/customer-email-validator': {
                'Magento_QuickCheckout/js/model/customer-email-validator-mixin': quickCheckoutMixinEnabled
            },
            'Magento_Checkout/js/view/authentication': {
                'Magento_QuickCheckout/js/view/authentication-mixin': quickCheckoutMixinEnabled
            },
            'Magento_Checkout/js/view/billing-address': {
                'Magento_QuickCheckout/js/view/billing-address-mixin': quickCheckoutMixinEnabled
            }
        }
    }
};
