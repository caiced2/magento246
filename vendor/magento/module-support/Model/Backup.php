<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Support\Model;

use Magento\Support\Helper\Shell as ShellHelper;
use Magento\Support\Console\Command\UtilityCheckCommand;

/**
 * Model of backup
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Backup extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_PROCESSING  = 0;
    const STATUS_COMPLETE    = 1;
    const STATUS_FAILED      = 2;
    const LOG_FILENAME       = 'backup.log';

    /**
     * Backup items
     *
     * @var array
     */
    protected $items = [];

    /**
     * @var \Magento\Support\Model\Backup\Config
     */
    protected $backupConfig;

    /**
     * @var \Magento\Support\Helper\Shell
     */
    protected $shellHelper;

    /**
     * @var Backup\Cmd\PhpFactory
     */
    protected $cmdPhpFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $directory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Support\Model\ResourceModel\Backup $resource
     * @param \Magento\Support\Model\ResourceModel\Backup\Collection $resourceCollection
     * @param Backup\Config $backupConfig
     * @param ShellHelper $shellHelper
     * @param Backup\Cmd\PhpFactory $cmdPhpFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Support\Model\ResourceModel\Backup $resource,
        \Magento\Support\Model\ResourceModel\Backup\Collection $resourceCollection,
        \Magento\Support\Model\Backup\Config $backupConfig,
        ShellHelper $shellHelper,
        \Magento\Support\Model\Backup\Cmd\PhpFactory $cmdPhpFactory,
        \Magento\Framework\Filesystem $filesystem,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->backupConfig = $backupConfig;
        $this->shellHelper = $shellHelper;
        $this->cmdPhpFactory = $cmdPhpFactory;
        $this->directory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
    }

    /**
     * Init Model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Support\Model\ResourceModel\Backup::class);
    }

    /**
     * Add item
     *
     * @param \Magento\Support\Model\Backup\AbstractItem $item
     * @param null|string $key
     * @return Backup
     */
    public function addItem(\Magento\Support\Model\Backup\AbstractItem $item, $key = null)
    {
        if ($key) {
            $this->items[$key] = $item;
        } else {
            $this->items[] = $item;
        }

        return $this;
    }

    /**
     * Get Item
     *
     * @param string $key
     * @return \Magento\Support\Model\Backup\AbstractItem|bool
     */
    public function getItem($key)
    {
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }

        return false;
    }

    /**
     * Get Items
     *
     * @return array
     */
    public function getItems()
    {
        if (!$this->items) {
            foreach ($this->backupConfig->getBackupItems() as $key => $item) {
                /** @var \Magento\Support\Model\Backup\AbstractItem $item */
                $item->loadItemByBackupIdAndType($this->getId(), $item->getType());
                $item->setBackup($this);
                $this->addItem($item, $key);
            }
        }
        return $this->items;
    }

    /**
     * Run cmd from backup items
     *
     * @throws \Magento\Framework\Exception\StateException
     * @return void
     */
    public function run()
    {
        $errors = $this->validate();
        if ($errors) {
            throw new \Magento\Framework\Exception\StateException(__(current($errors)));
        }

        $cmd = [];
        /** @var \Magento\Support\Model\Backup\AbstractItem $item */
        foreach ($this->getItems() as $item) {
            $cmd[] = $item->getCmd();
        }

        $cmd = implode('; ', $cmd);
        $cmd = sprintf("(%s) > %s 2>/dev/null &", $cmd, $this->shellHelper->getAbsoluteFilePath(self::LOG_FILENAME));

        $this->setStatus(self::STATUS_PROCESSING);
        $this->shellHelper->execute($cmd);
    }

    /**
     * Validate backup script
     *
     * @return array
     */
    public function validate()
    {
        $errors = [];
        $result = [];

        $os = $this->backupConfig->getUnsupportedOs();
        if ($os) {
            $errors[] = sprintf(__("Support Module doesn't support %s operation system")->render(), $os);
            return $errors;
        }

        if (!$this->shellHelper->isExecEnabled()) {
            $errors[] = __('Unable to create backup due to php exec function is disabled');
            return $errors;
        }

        /** @var \Magento\Support\Model\Backup\AbstractItem $item */
        foreach ($this->getItems() as $item) {
            $errors[] = $item->validate();
        }

        /** @var \Magento\Support\Model\Backup\Cmd\Php $cmd */
        $cmd = $this->cmdPhpFactory->create();
        $cmd->setScriptInterpreter($this->shellHelper->getUtility(ShellHelper::UTILITY_PHP));
        $cmd->setScriptName('bin/magento support:utility:check --' . UtilityCheckCommand::INPUT_KEY_HIDE_PATHS);

        $errors[] = $this->shellHelper->execute($cmd->generate());

        $errors = array_unique($errors);
        foreach ($errors as $error) {
            if ($error) {
                $result[] = $error;
            }
        }

        return $result;
    }

    /**
     * Generate random name if does not exist
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function beforeSave()
    {
        $this->generateRandomName();
        return parent::beforeSave();
    }

    /**
     * Set Backup Id to All Items
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function afterSave()
    {
        /** @var \Magento\Support\Model\Backup\AbstractItem $item */
        foreach ($this->getItems() as $item) {
            $item->setBackupId($this->getId());
            $item->save();
        }

        return parent::afterSave();
    }

    /**
     * Remove Backups files and log
     *
     * @return Backup
     */
    public function afterDelete()
    {
        /** @var \Magento\Support\Model\Backup\AbstractItem $item */
        foreach ($this->getItems() as $item) {
            $file = $this->shellHelper->getFilePath($item->getName());
            if ($this->directory->isExist($file) && $this->directory->isWritable($file)) {
                $this->directory->delete($file);
            }
        }

        $this->removeLogFile();

        return parent::afterDelete();
    }

    /**
     * Get Backup Name
     *
     * @return string
     */
    public function getName()
    {
        if (!$this->getData('name')) {
            $this->generateRandomName();
        }

        return $this->_getData('name');
    }

    /**
     * Generate Random Name for Backup
     *
     * @return string
     */
    protected function generateRandomName()
    {
        if (!$this->getData('name')) {
            // md5() here is not for cryptographic use.
            // phpcs:ignore Magento2.Security.InsecureFunction
            $this->setData('name', md5(time() . rand()));
        }

        return $this->getData('name');
    }

    /**
     * Update Status
     *
     * @return Backup
     */
    public function updateStatus()
    {
        if ($this->getStatus() == self::STATUS_COMPLETE) {
            return $this;
        }
        $this->updateLog();

        $allItemsCompleted = $this->isAllItemsCompleted();
        if ($allItemsCompleted) {
            $this->setStatus(self::STATUS_COMPLETE);
            $this->removeLogFile();
        } else {
            $this->setStatus(self::STATUS_PROCESSING);
        }

        if ($this->isItemsFilesNotExist() && $this->getLog()) {
            $this->setStatus(self::STATUS_FAILED);
        }

        return $this->save();
    }

    /**
     * Check if all items status is completed
     *
     * @return bool
     */
    protected function isAllItemsCompleted()
    {
        $complete = true;
        /** @var \Magento\Support\Model\Backup\AbstractItem $item*/
        foreach ($this->getItems() as $item) {
            if ($item->getStatus() != \Magento\Support\Model\Backup\AbstractItem::STATUS_COMPLETE) {
                $complete = false;
                break;
            }
        }

        return $complete;
    }

    /**
     * Check if items files not exist
     *
     * @return bool
     */
    protected function isItemsFilesNotExist()
    {
        $result = true;
        foreach ($this->getItems() as $item) {
            $file = $this->shellHelper->getFilePath($item->getName());
            if ($this->directory->isExist($file)) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Update Log Data for Current Backup
     *
     * @return Backup
     */
    protected function updateLog()
    {
        $logPath = $this->shellHelper->getFilePath(self::LOG_FILENAME);
        if ($this->directory->isExist($logPath)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $this->setLog(file_get_contents(
                $this->shellHelper->getAbsoluteFilePath(self::LOG_FILENAME)
            ));
        }

        return $this;
    }

    /**
     * Remove Log File
     *
     * @return Backup
     */
    protected function removeLogFile()
    {
        $logPath = $this->shellHelper->getFilePath(self::LOG_FILENAME);
        if ($this->directory->isExist($logPath) && $this->directory->isWritable($logPath)) {
            $this->directory->delete($logPath);
        }

        return $this;
    }

    /**
     * Prepare backup statuses
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_PROCESSING => __('Incomplete'),
            self::STATUS_COMPLETE => __('Complete'),
            self::STATUS_FAILED => __('Failed')
        ];
    }
}
