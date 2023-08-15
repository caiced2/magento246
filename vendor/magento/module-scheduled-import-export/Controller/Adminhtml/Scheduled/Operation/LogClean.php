<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation;

use Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation as OperationController;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;

class LogClean extends OperationController
{
    /**
     * Run log cleaning through http request.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $result = $this->_objectManager->get(\Magento\ScheduledImportExport\Cron\ScheduledLogClean::class)
            ->execute(true);
        if ($result) {
            $this->messageManager->addSuccess(__('You deleted the history files.'));
        } else {
            $this->messageManager->addError(__('We can\'t delete the history files right now.'));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath(
            'adminhtml/system_config/edit',
            ['section' => $this->getRequest()->getParam('section')]
        );
        return $resultRedirect;
    }
}
