<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Controller\Adminhtml\Report;

use Magento\Framework\Controller\ResultFactory;

/**
 * View report action
 */
class View extends \Magento\Support\Controller\Adminhtml\Report
{
    /**
     * @var \Magento\Support\Model\ReportFactory
     */
    protected $reportFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Support\Model\DataFormatter
     */
    protected $dataFormatter;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Support\Model\ReportFactory $reportFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Support\Model\DataFormatter $dataFormatter
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Support\Model\ReportFactory $reportFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Support\Model\DataFormatter $dataFormatter,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
    ) {
        $this->reportFactory = $reportFactory;
        $this->coreRegistry = $coreRegistry;
        $this->dataFormatter = $dataFormatter;
        $this->timeZone = $timeZone;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $model = $this->initReport();
            if (!$model->getId()) {
                $this->messageManager->addError(__('Requested system report no longer exists.'));

                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setPath('*/*/index');
                return $resultRedirect;
            }

            $modelDateCreatedString = $model->getCreatedAt();
            $dateString = $this->timeZone->formatDateTime(
                new \DateTime($modelDateCreatedString),
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::MEDIUM
            );

            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $resultPage->setActiveMenu('Magento_Support::support_report');
            $resultPage->getConfig()->getTitle()->prepend(
                $dateString . ' ' . $this->dataFormatter->getSinceTimeString($modelDateCreatedString)
            );
            return $resultPage;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e);
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Unable to read system report data to display.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $redirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');
        return $resultRedirect;
    }

    /**
     * Load system report from request
     *
     * @param string $idFieldName
     * @return \Magento\Support\Model\Report $model
     */
    protected function initReport($idFieldName = 'id')
    {
        $id = (int)$this->getRequest()->getParam($idFieldName);

        /** @var \Magento\Support\Model\Report $model */
        $model = $this->reportFactory->create();
        if ($id) {
            $model->load($id);
        }
        if (!$this->coreRegistry->registry('current_report')) {
            $this->coreRegistry->register('current_report', $model);
        }
        return $model;
    }
}
