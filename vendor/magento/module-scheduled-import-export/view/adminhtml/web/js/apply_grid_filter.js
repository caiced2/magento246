/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'mage/adminhtml/grid'
], function () {
    'use strict';

    return function () {
        var varienGrid, exportFilterGridJsObject, varienImportExportScheduled;

        /**
         * @returns {*}
         */
        function doFilter() {
            return varienGrid.prototype.doFilter.call(this, varienImportExportScheduled.modifyFilterGrid);
        }

        /**
         * @returns {*}
         */
        function resetFilter() {
            return varienGrid.prototype.resetFilter.call(this, varienImportExportScheduled.modifyFilterGrid);
        }

        if (window['export_filter_gridJsObject'] !== undefined) {

            exportFilterGridJsObject.resetFilter = resetFilter();
            exportFilterGridJsObject.doFilter = doFilter();
        }
    };
});
