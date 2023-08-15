/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

import {
    ConverterConfigInterface, ConverterDataInterface,
} from "Magento_PageBuilder/js/mass-converter/converter-interface";
import BaseWidgetDirective from "Magento_PageBuilder/js/mass-converter/widget-directive-abstract";

/**
 * Enables the settings of the content type to be stored as a widget directive.
 *
 * @api
 */
export default class WidgetDirective extends BaseWidgetDirective {
    /**
     * Convert value to internal format
     *
     * @param {object} data
     * @param {object} config
     * @returns {object}
     */
    public fromDom(data: ConverterDataInterface, config: ConverterConfigInterface): object {
        const attributes = super.fromDom(data, config);

        data.template = attributes.template;
        data.block_id = attributes.banner_ids;
        return data;
    }

    /**
     * Convert value to knockout format
     *
     * @param {object} data
     * @param {object} config
     * @returns {object}
     */
    public toDom(data: ConverterDataInterface, config: ConverterConfigInterface): object {
        const attributes = {
            type: "Magento\\Banner\\Block\\Widget\\Banner",
            display_mode: "fixed",
            rotate: "",

            template: data.template,
            banner_ids: data.block_id,
            unique_id: data.block_id,
            type_name: "Dynamic Blocks Rotator",
        };

        if (!attributes.banner_ids || !attributes.template) {
            return data;
        }

        data[config.html_variable] = this.buildDirective(attributes);
        return data;
    }
}
