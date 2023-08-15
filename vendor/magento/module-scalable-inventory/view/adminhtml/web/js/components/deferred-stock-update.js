/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @deprecated split database solution is deprecated and will be removed
 */
define([
    'Magento_Ui/js/form/components/group'
], function (Group) {
    'use strict';

    return Group.extend({
        defaults: {
            modules: {
                backordersField: '${ $.backordersField }'
            }
        },

        /**
         * Visibility listener
         */
        visibilityBackordersChanged: function () {
            this.changeVisibility();
        },

        /**
         * Value listener
         */
        valueBackordersChanged: function () {
            this.changeVisibility();
        },

        /**
         * Change visibility for deferredStockUpdate based on current visibility and value.
         */
        changeVisibility: function () {
            if (this.backordersField()) {
                if (this.backordersField().visible() && parseFloat(this.backordersField().value())) {
                    this.visible(true);
                } else {
                    this.visible(false);
                }
            }
        }
    });
});
