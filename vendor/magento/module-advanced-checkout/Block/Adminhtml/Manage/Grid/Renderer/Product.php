<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer;

/**
 * Adminhtml grid product name column renderer
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Product extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    /**
     * Render product name to add Configure link
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(\Magento\Framework\DataObject $row): string
    {
        $rendered = parent::render($row);
        $listType = $this->getColumn()->getGrid()->getListType();
        $product = $this->getProduct($row);
        if ($product !== null && $product->canConfigure()) {
            $style = '';
            $prodAttributes = sprintf('list_type = "%s" item_id = %s', $listType, $row->getId());
        } else {
            $style = 'disabled';
            $prodAttributes = 'disabled="disabled"';
        }
        $returnValue = sprintf(
            '<a href="javascript:void(0)" %s class="action-configure %s">%s</a>',
            $prodAttributes,
            $style,
            __('Configure')
        );
        return $returnValue . $rendered;
    }

    /**
     * Returns product
     *
     * @param \Magento\Framework\DataObject $row
     * @return \Magento\Catalog\Model\Product| null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProduct(\Magento\Framework\DataObject $row): ?\Magento\Catalog\Model\Product
    {
        $product = null;
        if ($row instanceof \Magento\Catalog\Model\Product) {
            $product = $row;
        } elseif (($row instanceof \Magento\Wishlist\Model\Item) || ($row instanceof \Magento\Sales\Model\Order\Item)) {
            $product = $row->getProduct();
        }
        return $product;
    }
}
