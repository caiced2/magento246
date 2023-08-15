/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/grid/paging/paging'
], function (Paging) {
    'use strict';

    return Paging.extend({
        defaults: {
            totalTmpl: 'Magento_Staging/grid/paging/paging-total',
            extendedSelections: [],
            imports: {
                extendedSelections: '${ $.selectProvider }:extendedSelections'
            }
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('extendedSelections');

            return this;
        }
    });
});
