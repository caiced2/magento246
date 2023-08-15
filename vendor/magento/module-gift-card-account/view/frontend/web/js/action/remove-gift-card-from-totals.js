/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(['jquery', 'mage/dataPost'], function ($, dataPost) {
        'use strict';

        return function (config, element) {
            element = $(element);

            element.click(function (event) {
                event.preventDefault();
                dataPost().postData({
                    action: element.attr('href'),
                    data: config
                });
            });
        };
    }
);
