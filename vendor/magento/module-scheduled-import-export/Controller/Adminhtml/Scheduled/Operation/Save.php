<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation as OperationController;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\ScheduledImportExport\Model\Scheduled\OperationFactory;
use Magento\ScheduledImportExport\Helper\Data as DataHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Save extends OperationController implements HttpPostActionInterface
{
    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\OperationFactory
     */
    protected $operationFactory;

    /**
     * @var \Magento\ScheduledImportExport\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\ScheduledImportExport\Model\Scheduled\OperationFactory $operationFactory
     * @param \Magento\ScheduledImportExport\Helper\Data $dataHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        OperationFactory $operationFactory,
        DataHelper $dataHelper,
        LoggerInterface $logger,
        ResolverInterface $localeResolver
    ) {
        $this->operationFactory = $operationFactory;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->localeResolver = $localeResolver;

        parent::__construct($context, $coreRegistry);
    }

    /**
     * Save operation action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $request = $this->getRequest();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if ($request->isPost()) {
            $data = $request->getPostValue();

            if (isset($data['id']) && !is_numeric($data['id'])
                || !isset($data['id']) && (!isset($data['operation_type']) || empty($data['operation_type']))
                || !is_array($data['start_time'])
            ) {
                $this->messageManager->addError(__('We can\'t save the scheduled operation right now.'));
                $resultRedirect->setPath('adminhtml/*/*', ['_current' => true]);
                return $resultRedirect;
            }
            $data['start_time'] = join(':', $data['start_time']);
            if (isset($data['export_filter']) && is_array($data['export_filter'])) {
                $data['entity_attributes']['export_filter'] = $data['export_filter'];
                if (isset($data['skip_attr']) && is_array($data['skip_attr'])) {
                    $data['entity_attributes']['skip_attr'] = array_filter($data['skip_attr'], 'intval');
                }
            }
            $data['file_info']['locale'] = $this->localeResolver->getLocale();

            try {
                /** @var \Magento\ScheduledImportExport\Model\Scheduled\Operation $operation */
                $operation = $this->operationFactory->create();
                $operation->setData($data);
                $operation->save();
                $this->messageManager->addSuccess(
                    $this->dataHelper->getSuccessSaveMessage($operation->getOperationType())
                );
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addError(__('We can\'t save the scheduled operation right now.'));
            }
        }
        $resultRedirect->setPath('adminhtml/scheduled_operation/index');
        return $resultRedirect;
    }
}
