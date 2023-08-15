/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Rule/rules',
    'prototype'
], function (VarienRulesForm) {
    'use strict';

    return function (config) {
        var segmentConditionsFieldset = new VarienRulesForm(config.jsObjectName, config.childUrl);

        if (config.isReadonly !== '') {
            segmentConditionsFieldset.setReadonly(true);
        }
        window[config.jsObjectName] = segmentConditionsFieldset;
    };
});
