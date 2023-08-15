<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Report\Customer\Customersegment;

class Refresh extends \Magento\CustomerSegment\Controller\Adminhtml\Report\Customer\Customersegment
{
    /**
     * Apply segment conditions to all customers
     *
     * @return void
     */
    public function execute()
    {
        $segment = $this->_initSegment();
        if ($segment) {
            try {
                if ($segment->getApplyTo() != \Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS) {
                    $segment->matchCustomers();
                }
                $this->messageManager->addSuccess(__('The Customer Segment data has been refreshed.'));
                $this->_redirect('*/*/detail', ['_current' => true]);
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/detail', ['_current' => true]);
        return;
    }
}
