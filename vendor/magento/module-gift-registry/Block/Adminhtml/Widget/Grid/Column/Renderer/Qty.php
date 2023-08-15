<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Widget\Grid\Column\Renderer;

/**
 * Column renderer for gift registry item grid qty column
 * @codeCoverageIgnore
 */
class Qty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render gift registry item qty as input html element
     *
     * @param  \Magento\Framework\DataObject $row
     * @return string
     */
    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        $value = $row->getData($this->getColumn()->getIndex()) * 1;

        $html = '<input type="text" ';
        $html .= 'name="items[' . $row->getItemId() . '][' . $this->getColumn()->getId() . ']"';
        $html .= 'value="' . $value . '"';
        $html .= 'class="input-text ' . $this->getColumn()->getInlineCss() . '"/>';
        return $html;
    }
}
