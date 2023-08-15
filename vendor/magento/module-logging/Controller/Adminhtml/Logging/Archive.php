<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Controller\Adminhtml\Logging;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Archive extends \Magento\Logging\Controller\Adminhtml\Logging implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Logging::backups';

    /**
     * Archive page
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Magento_Logging::system_magento_logging_backups');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Admin Actions Archive'));
        $this->_view->renderLayout();
    }
}
