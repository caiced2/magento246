/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

import $t from "mage/translate";
import HideShowOption from "Magento_PageBuilder/js/content-type-menu/hide-show-option";
import {OptionsInterface} from "Magento_PageBuilder/js/content-type-menu/option.types";
import BasePreview from "Magento_PageBuilder/js/content-type/block/preview";
import {DataObject} from "Magento_PageBuilder/js/data-store";

/**
 * @api
 */
export default class Preview extends BasePreview {
    protected messages = {
        NOT_SELECTED: $t("Empty Dynamic Block"),
        UNKNOWN_ERROR: $t("An unknown error occurred. Please try again."),
    };

    /**
     * @inheritDoc
     */
    public processBlockData(data: DataObject): void {
        // Only load if something changed
        this.displayPreviewPlaceholder(data, "block_id");

        if (data.block_id && data.template.length !== 0) {
            this.processRequest(data, "block_id", "name");
        }
    }

    /**
     * Return an array of options
     *
     * @returns {OptionsInterface}
     */
    public retrieveOptions(): OptionsInterface {
        const options = super.retrieveOptions();

        options.hideShow = new HideShowOption({
            preview: this,
            icon: HideShowOption.showIcon,
            title: HideShowOption.showText,
            action: this.onOptionVisibilityToggle,
            classes: ["hide-show-content-type"],
            sort: 40,
        });

        return options;
    }
}
