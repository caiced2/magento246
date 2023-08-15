<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Block\Adminhtml\Promo\Catalog;

/**
 * Class Plugin
 */
class Plugin
{
    /**
     * Check where button can be rendered
     *
     * @param \Magento\CatalogRule\Block\Adminhtml\Promo\Catalog $subject
     * @param \Magento\Backend\Block\Widget\Button\Item $item
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCanRender(
        \Magento\CatalogRule\Block\Adminhtml\Promo\Catalog $subject,
        \Magento\Backend\Block\Widget\Button\Item $item
    ) {
        if ($item->getId() === "apply_rules") {
            $subject->removeButton($item->getId());
        }
    }
}
