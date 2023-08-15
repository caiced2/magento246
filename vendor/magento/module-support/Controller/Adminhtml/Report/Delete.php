<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Controller\Adminhtml\Report;

use Magento\Framework\Controller\ResultFactory;

/**
 * Delete report action
 */
class Delete extends \Magento\Support\Controller\Adminhtml\Report
{
    /**
     * @var \Magento\Support\Model\ReportFactory
     */
    protected $reportFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Support\Model\ReportFactory $reportFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Support\Model\ReportFactory $reportFactory
    ) {
        $this->reportFactory = $reportFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id', 0);

        /** @var \Magento\Support\Model\Report $report */
        $report = $this->reportFactory->create()->load($id);

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');

        if (!$report->getId()) {
            $this->messageManager->addError(__('Unable to find a system report to delete.'));
            return $resultRedirect;
        }

        try {
            $report->delete();
            $this->messageManager->addSuccess(__('The system report has been deleted.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException(
                $e,
                __('An error occurred while deleting the system report. Please review log and try again.')
            );
        }
        return $resultRedirect;
    }
}
