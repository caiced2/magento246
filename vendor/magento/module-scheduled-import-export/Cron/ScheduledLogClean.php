<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;

/**
 * Clear old log files and folders by schedule.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ScheduledLogClean
{
    /**
     * Cron tab expression path
     */
    const CRON_STRING_PATH = 'crontab/default/jobs/magento_scheduled_import_export_log_clean/schedule/cron_expr';

    /**
     * Configuration path of log status
     */
    const LOG_CLEANING_ENABLE_PATH = 'system/magento_scheduled_import_export_log/enabled';

    /**
     * Configuration path of log save days
     */
    const SAVE_LOG_TIME_PATH = 'system/magento_scheduled_import_export_log/save_days';

    /**
     * Recipient email configuraiton path
     */
    const XML_RECEIVER_EMAIL_PATH = 'system/magento_scheduled_import_export_log/error_email';

    /**
     * Sender email configuraiton path
     */
    const XML_SENDER_EMAIL_PATH = 'system/magento_scheduled_import_export_log/error_email_identity';

    /**
     * Email template configuraiton path
     */
    const XML_TEMPLATE_EMAIL_PATH = 'system/magento_scheduled_import_export_log/error_email_template';

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_logDirectory;

    /**
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->_transportBuilder = $transportBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_logDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
    }

    /**
     * Clear old log files and folders
     *
     * @param bool $forceRun
     * @return bool|void
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute($forceRun = false)
    {
        $result = false;
        if (!$forceRun
            && !$this->_scopeConfig->getValue(
                self::CRON_STRING_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            && !$this->_scopeConfig->getValue(
                self::LOG_CLEANING_ENABLE_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        ) {
            return;
        }

        try {
            $logPath = \Magento\ScheduledImportExport\Model\Scheduled\Operation::LOG_DIRECTORY;

            try {
                $this->_logDirectory->create($logPath);
            } catch (FileSystemException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('We can\'t create directory "%1"', $logPath)
                );
            }

            if (!$this->_logDirectory->isWritable($logPath)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The directory "%1" is not writable.', $logPath)
                );
            }
            $saveTime = (int)$this->_scopeConfig->getValue(
                self::SAVE_LOG_TIME_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) + 1;
            $dateCompass = new \DateTime('-' . $saveTime . ' days');

            $pattern = '~' . Operation::FILE_HISTORY_DIRECTORY . '(\d{4})/(\d{2})/(\d{2})$~';
            foreach ($this->_getDirectoryList($logPath) as $directory) {
                if (!preg_match($pattern, $directory, $matches)) {
                    continue;
                }
                $directoryDate = new \DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3]);
                if ($forceRun || $directoryDate < $dateCompass) {
                    try {
                        $this->_logDirectory->delete($directory);
                    } catch (FileSystemException $e) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('We can\'t delete "%1" because the directory is not writable.', $directory)
                        );
                    }
                }
            }
            $result = true;
        } catch (\Exception $e) {
            $this->_sendEmailNotification(['warnings' => $e->getMessage()]);
            throw $e;
        }
        return $result;
    }

    /**
     * Parse log folder filesystem and find all directories on third nesting level
     *
     * @param string $logPath
     * @param int $level
     * @return string[]
     */
    protected function _getDirectoryList($logPath, $level = 1)
    {
        $result = [];

        $logPath = rtrim($logPath, '/');

        $entities = $this->_logDirectory->read($logPath);
        foreach ($entities as $entity) {
            if (!$this->_logDirectory->isDirectory($entity)) {
                continue;
            }

            $mergePart = $level < 4 ? $this->_getDirectoryList($entity, $level + 1) : [$entity];

            $result = array_merge($result, $mergePart);
        }
        return $result;
    }

    /**
     * Send email notification
     *
     * @param array $vars
     * @return $this
     */
    protected function _sendEmailNotification($vars)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $receiverEmail = $this->_scopeConfig->getValue(
            self::XML_RECEIVER_EMAIL_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if (!$receiverEmail) {
            return $this;
        }

        // Set all required params and send emails
        /** @var \Magento\Framework\Mail\TransportInterface $transport */
        $transport = $this->_transportBuilder->setTemplateIdentifier(
            $this->_scopeConfig->getValue(
                self::XML_TEMPLATE_EMAIL_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
        )->setTemplateOptions(
            [
                'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
            ]
        )->setTemplateVars(
            $vars
        )->setFrom(
            $this->_scopeConfig->getValue(
                self::XML_SENDER_EMAIL_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
        )->addTo(
            $receiverEmail
        )->getTransport();
        $transport->sendMessage();

        return $this;
    }
}
