<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Index extends \Magento\Invitation\Controller\Adminhtml\Index implements HttpGetActionInterface
{
    /**
     * Invitation list
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Magento_Invitation::customer_magento_invitation');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Invitations'));
        $this->_view->renderLayout();
    }
}
