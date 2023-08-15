<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Controller\Adminhtml\Backup;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Download backup action
 */
class Download extends \Magento\Support\Controller\Adminhtml\Backup
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Magento\Support\Helper\Shell
     */
    protected $shellHelper;

    /**
     * @var \Magento\Support\Model\BackupFactory
     */
    protected $backupFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Download constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Support\Helper\Shell $shellHelper
     * @param \Magento\Support\Model\BackupFactory $backupFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param Filesystem $filesystem
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Support\Helper\Shell $shellHelper,
        \Magento\Support\Model\BackupFactory $backupFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->shellHelper = $shellHelper;
        $this->backupFactory = $backupFactory;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Exception
     */
    public function execute()
    {
        $backupId   = $this->getRequest()->getParam('backup_id', 0);
        $type       = $this->getRequest()->getParam('type', 0);

        $backupItem = $this->getBackupItem($backupId, $type);
        $filePath = $this->getBackupItemPath($backupItem);

        if (!$this->filesystem->getDirectoryRead(DirectoryList::ROOT)->isExist($filePath)) {
            $this->messageManager->addError(__('File does not exist'));

            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('*/*/index');

            return $resultRedirect;
        }

        $this->fileFactory->create($backupItem->getName(), [
            'value' => $filePath,
            'type'  => 'filename'
        ]);
    }

    /**
     * Get backup item
     *
     * @param int $backupId
     * @param int $type
     * @return \Magento\Support\Model\Backup\AbstractItem|null
     */
    protected function getBackupItem($backupId, $type)
    {
        $backupItem = null;

        /** @var \Magento\Support\Model\Backup $backup */
        $backup = $this->backupFactory->create()->load($backupId);

        /** @var \Magento\Support\Model\Backup\AbstractItem $itemVal */
        foreach ($backup->getItems() as $itemVal) {
            if ($itemVal->getType() == $type) {
                $backupItem = $itemVal;
                break;
            }
        }

        return $backupItem;
    }

    /**
     * Get backup item path
     *
     * @param \Magento\Support\Model\Backup\AbstractItem|null $backupItem
     * @return null|string
     */
    protected function getBackupItemPath($backupItem)
    {
        $backupPath = null;

        if (is_object($backupItem)) {
            $backupPath = $this->shellHelper->getFilePath($backupItem->getName());
        }

        return $backupPath;
    }
}
