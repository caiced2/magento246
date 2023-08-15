<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Chooseorder extends \Magento\Rma\Controller\Adminhtml\Rma implements HttpGetActionInterface
{
    /**
     * Choose Order action during new RMA creation
     *
     * @return void
     */
    public function execute()
    {
        $this->_initCreateModel();
        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('New Return'));
        $this->_view->renderLayout();
    }
}
