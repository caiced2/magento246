<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Controller\Adminhtml\Report;

use Magento\Framework\Controller\ResultFactory;

/**
 * Create report action
 */
class Create extends \Magento\Support\Controller\Adminhtml\Report
{
    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $this->messageManager->addWarning(
            __(
                'After you make your selections, click the "Create" button.'
                . ' Then stand by while the System Report is generated. This may take a few minutes.'
                . ' You will receive a notification once this step is completed.'
            )
        );

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magento_Support::support_report');
        $resultPage->getConfig()->getTitle()->prepend(__('Create System Report'));
        return $resultPage;
    }
}
