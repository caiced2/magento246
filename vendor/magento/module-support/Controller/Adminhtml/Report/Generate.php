<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Controller\Adminhtml\Report;

use Magento\Framework\Controller\ResultFactory;

/**
 * Generate report action
 */
class Generate extends \Magento\Support\Controller\Adminhtml\Report
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
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->getRequest()->isPost()) {
            $resultRedirect->setPath('*/*/index');
            return $resultRedirect;
        }

        $formData = $this->getRequest()->getPost('general');
        $groups = isset($formData['report_groups']) ? $formData['report_groups'] : false;
        if (!$groups) {
            $this->messageManager->addError(__('No groups were specified to generate system report.'));
            $resultRedirect->setPath('*/*/create');
            return $resultRedirect;
        }

        try {
            /** @var \Magento\Support\Model\Report $model */
            $model = $this->reportFactory->create();
            $model->generate($groups);
            $model->save();

            $this->messageManager->addSuccess(__('The system report has been generated.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException(
                $e,
                __('An error occurred while the system report was being created. Please review the log and try again.')
            );
        }
        $resultRedirect->setPath('*/*/index');
        return $resultRedirect;
    }
}
