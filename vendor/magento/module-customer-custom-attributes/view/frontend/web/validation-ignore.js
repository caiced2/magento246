/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mageUtils'
], function ($, utils) {
    'use strict';

    return function (config) {
        var ignore;

        if (config.ignore) {
            ignore = 'input[id$="full"]';
        }

        if (config.hasUserDefinedAttributes) {
            ignore = ignore ? ignore + ', ' + 'input[id$=\'_value\']' : 'input[id$=\'_value\']';
            ignore = ':hidden:not(' + ignore + ')';
        } else if (config.isDobEnabled) {
            ignore = ':hidden:not(' + ignore + ')';
        } else {
            ignore = ignore ? ':hidden:not(' + ignore + ')' : ':hidden';
        }

        return utils.extend(config, {
            ignore: ignore
        });
    };
});
