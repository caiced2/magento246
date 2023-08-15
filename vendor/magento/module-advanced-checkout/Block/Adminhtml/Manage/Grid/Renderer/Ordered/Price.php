<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer\Ordered;

/**
 * Adminhtml grid product price column custom renderer for last ordered items
 */
class Price extends \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Price
{
    /**
     * Render price for last ordered item
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     * @throws \Zend_Currency_Exception
     */
    public function render(\Magento\Framework\DataObject $row): string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->getProduct($row);
        $priceInitial = '';
        if ($product !== null) {
            // Show base price of product - the real price will be shown when user will configure product (if needed)
            $priceInitial = $product->getPrice() * 1;

            $priceInitial = (float)$priceInitial * $this->_getRate($row);
            $priceInitial = sprintf('%f', $priceInitial);
            $currencyCode = $this->_getCurrencyCode($row);
            if ($currencyCode) {
                $priceInitial = $this->_localeCurrency->getCurrency($currencyCode)->toCurrency($priceInitial);
            }
        }

        return $priceInitial;
    }

    /**
     * Returns product
     *
     * @param \Magento\Framework\DataObject $row
     * @return \Magento\Catalog\Model\Product|null
     */
    private function getProduct(\Magento\Framework\DataObject $row): ?\Magento\Catalog\Model\Product
    {
        return $row->getProduct();
    }
}
