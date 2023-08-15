/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
(function (factory) {
    'use strict';

    if (typeof define !== 'undefined' && define.amd) {
        define([
            'jquery',
            'jquery/ui',
            'mage/backend/validation'
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($) {
    'use strict';

    /**
     * @return {*}
     */
    $.validator.prototype.checkForm = function () {
        var lastElements = [],
            i, elements, className, j;

        this.prepareForm();

        for (i = 0, elements = this.currentElements = this.elements(); elements[i]; i++) {
            className = $(elements[i]).attr('class');

            if (className.search(/rma-action-links-/i) !== -1) {
                lastElements.push(elements[i]);
                continue; //jscs:ignore disallowKeywords
            }
            this.check(elements[i]);
        }
        this.showErrors();

        for (j = 0; lastElements[j]; j++) {
            this.check(lastElements[j]);
        }

        return this.valid();
    };
}));
