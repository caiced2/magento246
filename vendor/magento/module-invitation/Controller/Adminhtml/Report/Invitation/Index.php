<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Adminhtml\Report\Invitation;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Index extends \Magento\Invitation\Controller\Adminhtml\Report\Invitation implements HttpGetActionInterface
{
    /**
     * General report action
     *
     * @return void
     */
    public function execute()
    {
        $this->_initAction()->_setActiveMenu(
            'Magento_Invitation::report_magento_invitation_general'
        )->_addBreadcrumb(
            __('General Report'),
            __('General Report')
        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Invitations Report'));
        $this->_view->renderLayout();
    }
}
