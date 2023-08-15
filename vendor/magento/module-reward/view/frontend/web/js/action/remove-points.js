/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/dataPost'
], function ($, dataPost) {
    'use strict';

    $.widget('mage.removeRewardPoints', {

        /**
         * Create widget
         * @type {Object}
         */
        _create: function () {
            this.element.on('click', $.proxy(function () {
                this.removePoints();
            }, this));
        },

        /**
         * Remove reward points from item.
         *
         * @return void
         */
        removePoints: function () {
            dataPost().postData({
                action: this.options.removeUrl,
                data: {}
            });
        }
    });

    return $.mage.removeRewardPoints;
});
