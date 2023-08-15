<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Support\Model\Backup;

use Magento\Support\Helper\Shell as ShellHelper;

/**
 * General abstract class for backup items
 *
 * phpcs:disable Magento2.Classes.AbstractApi
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
abstract class AbstractItem extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_PROCESSING = 0;
    const STATUS_COMPLETE   = 1;

    /**
     * @var \Magento\Support\Model\BackupFactory
     */
    protected $backupFactory;

    /**
     * @var \Magento\Support\Helper\Shell
     */
    protected $shellHelper;

    /**
     * @var \Magento\Support\Model\Backup\Cmd\PhpFactory
     */
    protected $cmdPhpFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $directory;

    /**
     * @var \Magento\Support\Model\Backup\Cmd\Php
     */
    protected $cmdObject;

    /**
     * @var \Magento\Support\Model\Backup
     */
    protected $backup;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Support\Model\ResourceModel\Backup\Item $resource
     * @param \Magento\Support\Model\ResourceModel\Backup\Item\Collection $resourceCollection
     * @param \Magento\Support\Model\BackupFactory $backupFactory
     * @param \Magento\Support\Helper\Shell $shellHelper
     * @param Cmd\PhpFactory $cmdPhpFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Support\Model\ResourceModel\Backup\Item $resource,
        \Magento\Support\Model\ResourceModel\Backup\Item\Collection $resourceCollection,
        \Magento\Support\Model\BackupFactory $backupFactory,
        \Magento\Support\Helper\Shell $shellHelper,
        \Magento\Support\Model\Backup\Cmd\PhpFactory $cmdPhpFactory,
        \Magento\Framework\Filesystem $filesystem,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->backupFactory = $backupFactory;
        $this->shellHelper = $shellHelper;
        $this->cmdPhpFactory = $cmdPhpFactory;
        $this->directory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
    }

    /**
     * Init Resource Model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Support\Model\ResourceModel\Backup\Item::class);
    }

    /**
     * Set Backup
     *
     * @param \Magento\Support\Model\Backup $backup
     * @return void
     */
    public function setBackup(\Magento\Support\Model\Backup $backup)
    {
        $this->backup = $backup;
    }

    /**
     * Get Backup
     *
     * @return \Magento\Support\Model\Backup
     */
    public function getBackup()
    {
        if (!$this->backup) {
            $this->backup = $this->backupFactory->create()->load($this->getBackupId());
        }

        return $this->backup;
    }

    /**
     * Set Cmd object
     *
     * @param \Magento\Support\Model\Backup\Cmd\Php $cmd
     * @return void
     */
    public function setCmdObject(\Magento\Support\Model\Backup\Cmd\Php $cmd)
    {
        $this->cmdObject = $cmd;
    }

    /**
     * Get Command object
     *
     * @return \Magento\Support\Model\Backup\Cmd\Php
     */
    public function getCmdObject()
    {
        if (!$this->cmdObject) {
            $this->cmdObject = $this->cmdPhpFactory->create();

            $this->cmdObject->setScriptInterpreter($this->shellHelper->getUtility(ShellHelper::UTILITY_PHP));
            $this->setCmdScriptName();
            $this->cmdObject->setName($this->getBackup()->getName());
            $this->cmdObject->setOutput($this->shellHelper->getAbsoluteOutputPath());
        }

        return $this->cmdObject;
    }

    /**
     * Set script name to $this->cmdObject
     *
     * @return void
     */
    abstract protected function setCmdScriptName();

    /**
     * Get Command
     *
     * @return string
     */
    public function getCmd()
    {
        return $this->getCmdObject()->generate();
    }

    /**
     * Update Status
     *
     * @return void
     */
    public function updateStatus()
    {
        $fileName = $this->getName();
        $file = $this->shellHelper->getFilePath($fileName);
        $currentStatus = $this->getStatus();

        if ($currentStatus == self::STATUS_COMPLETE) {
            return;
        }

        if ($this->directory->isExist($file)) {
            if (!$this->shellHelper->isFileLocked($fileName)) {
                $this->setStatus(self::STATUS_COMPLETE);
                $this->updateFileInfo();
            } else {
                $this->setStatus(self::STATUS_PROCESSING);
            }
        } else {
            $this->setStatus(self::STATUS_PROCESSING);
        }

        $this->save();
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        $name = $this->getBackup()->getName();
        $name = sprintf("%s.%s", $name, $this->getOutputFileExtension());

        return $name;
    }

    /**
     * Get virtual column db_name
     *
     * @return string
     */
    public function getDbName()
    {
        $name = $this->getBackup()->getData('db_name');
        $name = sprintf("%s.%s", $name, $this->getOutputFileExtension());

        return $name;
    }

    /**
     * Load Item by Backup ID & Type
     *
     * @param int $backupId
     * @param int $type
     * @return \Magento\Support\Model\ResourceModel\Backup\Item
     */
    public function loadItemByBackupIdAndType($backupId, $type)
    {
        return $this->getResource()->loadItemByBackupIdAndType($this, $backupId, $type);
    }

    /**
     * Validate
     *
     * @return string
     */
    public function validate()
    {
        $outputPath = $this->shellHelper->getOutputPath();
        $error = '';

        if (!$this->directory->isWritable($outputPath) || !$this->directory->isReadable($outputPath)) {
            $error = sprintf(
                __('Directory %s should have writable & readable permissions')->render(),
                $outputPath
            );
        }

        return $error;
    }

    /**
     * Update File Info
     *
     * @return void
     */
    protected function updateFileInfo()
    {
        $size = $this->shellHelper->getFileSize($this->getName());
        $this->setSize($size);
    }
}
