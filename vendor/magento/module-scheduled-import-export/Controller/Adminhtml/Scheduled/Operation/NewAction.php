<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation;

use Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation as OperationController;

class NewAction extends OperationController
{
    /**
     * Create new operation action.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $operationType = $this->getRequest()->getParam('type');
        $resultPage = $this->createPage();
        $resultPage->getConfig()->getTitle()->prepend(
            $this->_objectManager->get(\Magento\ScheduledImportExport\Helper\Data::class)
                ->getOperationHeaderText($operationType, 'new')
        );
        return $resultPage;
    }
}
