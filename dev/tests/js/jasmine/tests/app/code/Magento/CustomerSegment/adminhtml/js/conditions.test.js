/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/* eslint-disable max-nested-callbacks */
define([
    'Magento_CustomerSegment/js/conditions',
    'jquery'
], function (Segment, $) {
    'use strict';

    describe('CustomerSegment/js/conditions', function () {
        // eslint-disable-next-line no-unused-vars
        var segment,
            config = {
                jsObjectName: 'test_js_object_name',
                childUrl: 'some_url'
            };

        beforeEach(function () {
            $('<fieldset id="' + config.jsObjectName + '" />').appendTo(document.body);
            segment = new Segment(config);
        });

        it('Check if CustomerSegment js form object was created', function () {
            expect(window[config.jsObjectName]).toBeDefined();
        });
    });
});
