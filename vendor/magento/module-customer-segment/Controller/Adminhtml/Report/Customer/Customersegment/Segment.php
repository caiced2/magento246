<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Report\Customer\Customersegment;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\CustomerSegment\Controller\Adminhtml\Report\Customer\Customersegment as CustomerSegmentAction;

class Segment extends CustomerSegmentAction implements HttpGetActionInterface
{
    /**
     * Segment Action
     *
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Customer Segment Report'));
        $this->_view->renderLayout();
    }
}
