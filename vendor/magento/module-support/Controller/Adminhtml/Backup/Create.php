<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Support\Controller\Adminhtml\Backup;

use \Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\NotFoundException;

/**
 * Create backup action
 */
class Create extends \Magento\Support\Controller\Adminhtml\Backup
{
    /**
     * @var \Magento\Support\Model\ResourceModel\Backup\Collection
     */
    protected $backupCollection;

    /**
     * @var \Magento\Support\Model\Backup
     */
    protected $backupModel;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Support\Model\Backup $backupModel
     * @param \Magento\Support\Model\ResourceModel\Backup\Collection $backupCollection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Support\Model\Backup $backupModel,
        \Magento\Support\Model\ResourceModel\Backup\Collection $backupCollection
    ) {
        parent::__construct($context);
        $this->backupModel = $backupModel;
        $this->backupCollection = $backupCollection;
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        try {
            $this->issetProcessingBackups();
            $this->backupModel->run();
            $this->backupModel->save();
            $this->messageManager->addSuccess(__('The backup has been saved.'));
        } catch (StateException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (NotFoundException $e) {
            $this->messageManager->addException($e, __('Failed to save backup with error: %1', $e->getMessage()));
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('An error occurred while saving backup'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');

        return $resultRedirect;
    }

    /**
     * Check if isset processing backups
     *
     * @return $this
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function issetProcessingBackups()
    {
        $this->backupCollection->addProcessingStatusFilter();

        if ($this->backupCollection->count() > 0) {
            throw new StateException(__('All processes should be completed.'));
        }

        return $this;
    }
}
