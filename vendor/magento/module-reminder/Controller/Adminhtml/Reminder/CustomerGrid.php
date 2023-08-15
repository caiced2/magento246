<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Controller\Adminhtml\Reminder;

class CustomerGrid extends \Magento\Reminder\Controller\Adminhtml\Reminder
{
    /**
     *  Customer grid ajax action
     *
     * @return void
     */
    public function execute()
    {
        if ($this->_initRule('rule_id')) {
            $block = $this->_view->getLayout()->createBlock(
                \Magento\Reminder\Block\Adminhtml\Reminder\Edit\Tab\Customers::class
            );
            $this->getResponse()->setBody($block->toHtml());
        }
    }
}
