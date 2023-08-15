<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Controller\Adminhtml\Logging;

class Details extends \Magento\Logging\Controller\Adminhtml\Logging
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Logging::magento_logging_events';

    /**
     * View logging details
     *
     * @return void
     */
    public function execute()
    {
        $eventId = $this->getRequest()->getParam('event_id');
        /** @var \Magento\Logging\Model\Event $model */
        $model = $this->_eventFactory->create()->load($eventId);
        if (!$model->getId()) {
            $this->_redirect('adminhtml/*/');
            return;
        }
        $this->_coreRegistry->register('current_event', $model);

        $this->_view->loadLayout();
        $this->_setActiveMenu('Magento_Logging::system_magento_logging_events');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__("Log Entry #%1", $eventId));
        $this->_view->renderLayout();
    }
}
