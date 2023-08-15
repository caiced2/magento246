<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Controller\Adminhtml\Report;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Report download action
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Download extends \Magento\Support\Controller\Adminhtml\Report\View
{
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

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
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Support\Model\ReportFactory $reportFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Support\Model\DataFormatter $dataFormatter,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->layoutFactory = $layoutFactory;
        $this->fileFactory = $fileFactory;
        parent::__construct(
            $context,
            $reportFactory,
            $coreRegistry,
            $dataFormatter,
            $timeZone
        );
    }

    /**
     * @inheirtDoc
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

            /** @var \Magento\Framework\View\Layout $layout */
            $layout = $this->layoutFactory->create();
            $content = $layout->createBlock(
                \Magento\Support\Block\Adminhtml\Report\Export\Html::class,
                'report.export.html'
            )
                ->setData(['report' => $model])
                ->toHtml();

            return $this->fileFactory->create(
                $model->getFileNameForReportDownload(),
                $content,
                \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e);
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Unable to read system report data to display.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');
        return $resultRedirect;
    }
}
