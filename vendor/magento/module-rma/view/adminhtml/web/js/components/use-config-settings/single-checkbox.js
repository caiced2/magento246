/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/single-checkbox'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            disableIsReturnable: false,
            listens: {
                disabled: 'toggleDisableIsReturnable',
                checked: 'toggleDisableIsReturnable onCheckedChanged'
            }
        },

        /**
         * Handle checked and disabled changes to calculate disableIsReturnable value
         */
        toggleDisableIsReturnable: function () {
            this.disableIsReturnable(this.checked() || this.disabled());
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super()
                .observe('disableIsReturnable');

            return this;
        }
    });
});
