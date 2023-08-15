<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation;

use Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation as OperationController;

class Edit extends OperationController
{
    /**
     * Edit operation action.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->createPage();
        /** @var \Magento\ScheduledImportExport\Model\Scheduled\Operation $operation */
        $operation = $this->coreRegistry->registry('current_operation');
        $operationType = $operation->getOperationType();
        /** @var \Magento\ScheduledImportExport\Helper\Data $helper */
        $helper = $this->_objectManager->get(\Magento\ScheduledImportExport\Helper\Data::class);
        $resultPage->getConfig()->getTitle()->prepend(
            $helper->getOperationHeaderText($operationType, 'edit')
        );
        return $resultPage;
    }
}
