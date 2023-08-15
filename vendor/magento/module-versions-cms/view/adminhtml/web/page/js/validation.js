/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery/ui',
    'mage/validation'
], function ($) {
    'use strict';

    $.widget('mage.validation', $.mage.validation, {
        options: {
            ignore: 'form form input, form form select, form form textarea'
        }
    });
});
