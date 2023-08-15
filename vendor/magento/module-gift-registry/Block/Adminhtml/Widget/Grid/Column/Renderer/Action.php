<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Widget\Grid\Column\Renderer;

/**
 * Column renderer for gift registry items grid action column
 * @codeCoverageIgnore
 */
class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render gift registry item action as select html element
     *
     * @param  \Magento\Framework\DataObject $row
     * @return string
     */
    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setId(
            $this->getColumn()->getId()
        )->setName(
            'items[' . $row->getItemId() . '][action]'
        )->setOptions(
            $this->getColumn()->getOptions()
        );
        return $select->getHtml();
    }
}
