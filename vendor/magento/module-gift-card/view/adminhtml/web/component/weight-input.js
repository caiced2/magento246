/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({

        /**
         * Checks is component virtual
         *
         * @param {*} value
         * @returns {Boolean}
         */
        isVirtual: function (value) {
            if (value) {
                this.disabled(false);

                return false;
            }

            this.disabled(true);
        }
    });
});
