/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery/ui',
    'mage/backend/form'
], function ($) {
    'use strict';

    $.widget('mage.form', $.mage.form, {
        options: {
            handlersData: {
                preview: {
                    target: '_blank'
                },
                saveAndPublish: {
                    action: {
                        args: {
                            back: 'publish'
                        }
                    }
                }
            }
        },

        /**
         * Process preview action before form submit.
         * @param {String} handlerName
         * @protected
         */
        _beforeSubmit: function (handlerName) {
            if (handlerName === 'preview' && this.options.handlersData[handlerName].action) {
                this.element.prop(this._processData(this.options.handlersData[handlerName]));
            } else {
                this._superApply(arguments);
            }
        }
    });
});
