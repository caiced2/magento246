<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute as AttributeAction;

class Index extends AttributeAction implements HttpGetActionInterface
{
    /**
     * Attributes grid
     *
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Customer Address Attributes'));
        $this->_view->renderLayout();
    }
}
