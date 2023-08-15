<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Logging archive grid  item renderer
 */
namespace Magento\Logging\Block\Adminhtml\Grid\Renderer;

class Download extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    /**
     * Renders grid column
     *
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function _getValue(\Magento\Framework\DataObject $row)
    {
        return '<a href="' . $this->getUrl(
            'adminhtml/*/download',
            ['basename' => $row->getBasename()]
        ) . '">' . $row->getBasename() . '</a>';
    }
}
