<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Block\Adminhtml\Widget\Grid\Column\Renderer;

/**
 * Column renderer for customer email
 */
class Email extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render customer email as mailto link
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        $customerEmail = $this->escapeHtml($row->getData($this->getColumn()->getIndex()));
        return '<a href="mailto:' . $customerEmail . '">' . $this->escapeHtml($customerEmail) . '</a>';
    }
}
