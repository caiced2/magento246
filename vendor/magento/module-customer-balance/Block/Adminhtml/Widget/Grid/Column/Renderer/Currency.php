<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Block\Adminhtml\Widget\Grid\Column\Renderer;

/**
 * Currency cell renderer for customerbalance grids
 */
class Currency extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Currency
{
    /**
     * @var array
     */
    protected $_websiteBaseCurrencyCodes = [];

    /**
     * Get currency code by row data
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    protected function _getCurrencyCode($row)
    {
        $websiteId = $row->getData('website_id');
        $orphanCurrency = $row->getData('base_currency_code');
        if ($orphanCurrency !== null) {
            return $orphanCurrency;
        }
        if (!isset($this->_websiteBaseCurrencyCodes[$websiteId])) {
            $this->_websiteBaseCurrencyCodes[$websiteId] = $this->_storeManager->getWebsite(
                $websiteId
            )->getBaseCurrencyCode();
        }
        return $this->_websiteBaseCurrencyCodes[$websiteId];
    }

    /**
     * Stub getter for exchange rate
     *
     * @param \Magento\Framework\DataObject $row
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getRate($row)
    {
        return 1;
    }

    /**
     * Returns HTML for CSS
     *
     * @return string
     */
    public function renderCss()
    {
        return $this->getColumn()->getCssClass() . ' a-left';
    }
}
