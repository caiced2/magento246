<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block\Adminhtml\Widget\Grid\Column\Renderer;

class Currency extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Currency
{
    /**
     * @var array
     */
    protected static $_websiteBaseCurrencyCodes = [];

    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    protected function _getCurrencyCode($row)
    {
        $websiteId = $row->getWebsiteId();
        $code = $this->_storeManager->getWebsite($websiteId)->getBaseCurrencyCode();
        self::$_websiteBaseCurrencyCodes[$websiteId] = $code;

        return self::$_websiteBaseCurrencyCodes[$websiteId];
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @return float|int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getRate($row)
    {
        return 1;
    }
}
