<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PromotionPermissions\Block\Adminhtml\Promo\Catalog;

use Magento\PromotionPermissions\Helper\Data as DataHelper;
use Magento\CatalogRule\Block\Adminhtml\Promo\Catalog as PromoCatalogBlock;
use Magento\Backend\Block\Widget\Button\Item as ButtonItemWidget;

/**
 * Plugin for Magento\CatalogRule\Block\Adminhtml\Promo\Catalog
 */
class Plugin
{
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(DataHelper $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * Check whether button can be rendered
     *
     * @param PromoCatalogBlock $subject
     * @param bool $result
     * @param ButtonItemWidget $item
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanRender(PromoCatalogBlock $subject, $result, ButtonItemWidget $item)
    {
        if ($result && !$this->dataHelper->getCanAdminEditCatalogRules()) {
            return !in_array($item->getId(), ['add', 'apply_rules']);
        }

        return $result;
    }
}
