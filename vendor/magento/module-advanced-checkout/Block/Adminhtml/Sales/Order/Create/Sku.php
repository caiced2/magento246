<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Sales\Order\Create;

/**
 * "Add by SKU" accordion
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Sku extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{
    /**
     * Define ID
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        $this->setId('sales_order_create_sku');
    }

    /**
     * Retrieve accordion header
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getHeaderText()
    {
        return __('Add to Order by SKU');
    }

    /**
     * Retrieve CSS class for header
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getHeaderCssClass()
    {
        return 'head-catalog-product';
    }

    /**
     * Retrieve "Add to order" button
     *
     * @return string
     */
    public function getButtonsHtml()
    {
        $addButtonData = [
            'label' => $this->escapeHtmlAttr(__('Add to Order')),
            'onclick' => 'addBySku.submitSkuForm()',
            'class' => 'action-add action-secondary',
        ];
        return $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            $addButtonData
        )->toHtml();
    }
}
