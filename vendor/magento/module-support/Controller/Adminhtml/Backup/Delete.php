<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Controller\Adminhtml\Backup;

/**
 * Delete backup action
 */
class Delete extends \Magento\Support\Controller\Adminhtml\Backup
{
    /**
     * @var \Magento\Support\Model\BackupFactory
     */
    protected $backupFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Support\Model\BackupFactory $backupFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Support\Model\BackupFactory $backupFactory
    ) {
        parent::__construct($context);
        $this->backupFactory = $backupFactory;
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('id', 0);

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');

        /** @var \Magento\Support\Model\Backup $backup */
        $backup = $this->backupFactory->create()->load($id);

        if (!$backup->getId()) {
            $this->messageManager->addError(__('Wrong param id'));
            return $resultRedirect;
        }

        try {
            $backup->delete();
            $this->messageManager->addSuccess(__('The backup has been deleted.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Cannot delete backup'));
        }

        return $resultRedirect;
    }
}
