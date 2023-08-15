<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PromotionPermissions\Block\Adminhtml\Promo\Quote;

use Magento\PromotionPermissions\Helper\Data as DataHelper;
use Magento\SalesRule\Block\Adminhtml\Promo\Quote as PromoQuoteBlock;
use Magento\Backend\Block\Widget\Button\Item as ButtonItemWidget;

/**
 * Plugin for Magento\SalesRule\Block\Adminhtml\Promo\Quote
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
     * @param PromoQuoteBlock $subject
     * @param bool $result
     * @param ButtonItemWidget $item
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanRender(PromoQuoteBlock $subject, $result, ButtonItemWidget $item)
    {
        if ($result && !$this->dataHelper->getCanAdminEditSalesRules()) {
            return !in_array($item->getId(), ['add']);
        }

        return $result;
    }
}
