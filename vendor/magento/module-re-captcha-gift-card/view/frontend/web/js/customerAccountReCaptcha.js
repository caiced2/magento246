/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// jscs:disable jsDoc

/* global grecaptcha */
define(
    [
        'Magento_ReCaptchaFrontendUi/js/reCaptcha'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            /**
             * Auto-trigger validation so that ReCaptcha is ready for Quick Check
             *
             * @param {Object} parentForm
             * @param {String} widgetId
             */
            initParentForm: function (parentForm, widgetId) {
                this._super(parentForm, widgetId);

                if (this.getIsInvisibleRecaptcha()) {
                    grecaptcha.execute(widgetId);
                } else {
                    grecaptcha.getResponse(widgetId);
                }
            },

            /**
             * Recording the token
             *
             * @param {String} token
             */
            reCaptchaCallback: function (token) {
                if (this.getIsInvisibleRecaptcha() && this.tokenField) {
                    this.tokenField.value = token;
                }
            }
        });
    }
);
