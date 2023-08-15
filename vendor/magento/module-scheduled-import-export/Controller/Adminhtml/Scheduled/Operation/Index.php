<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation as OperationController;

class Index extends OperationController implements HttpGetActionInterface
{
    /**
     * Index action.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->createPage();
        return $resultPage;
    }
}
