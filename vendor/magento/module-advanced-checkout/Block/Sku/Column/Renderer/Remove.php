<?php
/**
 * SKU failed information block renderer
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Sku\Column\Renderer;

class Remove extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Button
{
    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $removeButtonHtml = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class,
            '',
            [
                'data' => [
                    'class' => 'delete',
                    'label' => 'Remove',
                    'onclick' => 'addBySku.removeFailedItem(this)',
                    'type' => 'button',
                ]
            ]
        );

        return $removeButtonHtml->toHtml();
    }
}
