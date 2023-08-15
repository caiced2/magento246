<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Model\Scheduled;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ScheduledImportExport\Model\Scheduled\Operation\Data;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Export\ConfigInterface as ExportConfigInterface;

/**
 * Operation model
 *
 * @api
 *
 * @method string getOperationType() getOperationType()
 * @method \Magento\ScheduledImportExport\Model\Scheduled\Operation setOperationType() setOperationType(string $value)
 * @method string getEntityType() getEntityType()
 * @method string getEntitySubtype() getEntitySubtype()
 * @method \Magento\ScheduledImportExport\Model\Scheduled\Operation setEntityType() setEntityType(string $value)
 * @method \Magento\ScheduledImportExport\Model\Scheduled\Operation setEntitySubtype() setEntitySubtype(string $value)
 * @method string|array getStartTime() getStartTime()
 * @method \Magento\ScheduledImportExport\Model\Scheduled\Operation setStartTime() setStartTime(string $value)
 * @method string|array getFileInfo() getFileInfo()
 * @method string|array getEntityAttributes() getEntityAttributes()
 * @method string getBehavior() getBehavior()
 * @method string getForceImport() getForceImport()
 * @method \Magento\ScheduledImportExport\Model\Scheduled\Operation setLastRunDate(int $value) setLastRunDate()
 * @method int getLastRunDate() getLastRunDate()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Operation extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Log directory for import/export
     */
    public const LOG_DIRECTORY = 'import_export/';

    /**
     * File history directory for import/export
     */
    public const FILE_HISTORY_DIRECTORY = 'history/';

    /**
     * Email config prefix
     */
    public const CONFIG_PREFIX_EMAILS = 'trans_email/ident_';

    /**
     * Cron config template
     */
    public const CRON_STRING_PATH = 'crontab/default/jobs/scheduled_operation_%d/%s';

    /**
     * Cron callback config
     */
    public const CRON_MODEL = 'Magento\ScheduledImportExport\Model\Observer::processScheduledOperation';

    /**
     * Cron job name prefix for import/export
     */
    public const CRON_JOB_NAME_PREFIX = 'scheduled_operation_';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateModel;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;

    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\Operation\DataFactory
     */
    protected $_operationFactory;

    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\Operation\GenericFactory
     */
    protected $_schedOperFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * Filesystem instance
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Filesystem\Io\Ftp
     */
    protected $ftpAdapter;

    /**
     * Serializer Instance
     *
     * @var Json
     */
    protected $serializer;

    /**
     * File instance
     *
     * @var File
     */
    private $file;

    /**
     * @var ExportConfigInterface
     */
    private $exportConfig;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\ScheduledImportExport\Model\Scheduled\Operation\GenericFactory $schedOperFactory
     * @param \Magento\ScheduledImportExport\Model\Scheduled\Operation\DataFactory $operationFactory
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateModel
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Filesystem\Io\Ftp $ftpAdapter
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     * @param File|null $file
     * @param ExportConfigInterface $exportConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\ScheduledImportExport\Model\Scheduled\Operation\GenericFactory $schedOperFactory,
        \Magento\ScheduledImportExport\Model\Scheduled\Operation\DataFactory $operationFactory,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Filesystem\Io\Ftp $ftpAdapter,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null,
        File $file = null,
        ExportConfigInterface $exportConfig = null
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_dateModel = $dateModel;
        $this->_configValueFactory = $configValueFactory;
        $this->_operationFactory = $operationFactory;
        $this->_schedOperFactory = $schedOperFactory;
        $this->_storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->string = $string;
        $this->_transportBuilder = $transportBuilder;
        $this->ftpAdapter = $ftpAdapter;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->file = $file ?: ObjectManager::getInstance()->get(File::class);
        $this->exportConfig = $exportConfig ?: ObjectManager::getInstance()->get(ExportConfigInterface::class);

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_init(\Magento\ScheduledImportExport\Model\ResourceModel\Scheduled\Operation::class);
    }

    /**
     * Send email notification
     *
     * @param array $vars
     * @return $this
     */
    public function sendEmailNotification($vars = [])
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $copyTo = $this->getEmailCopy() ? explode(',', $this->getEmailCopy()) : [];
        $copyMethod = $this->getEmailCopyMethod();

        $receiverEmail = $this->_scopeConfig->getValue(
            self::CONFIG_PREFIX_EMAILS . $this->getEmailReceiver() . '/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $receiverName = $this->_scopeConfig->getValue(
            self::CONFIG_PREFIX_EMAILS . $this->getEmailReceiver() . '/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        // Set all required params and send emails
        $this->_transportBuilder->setTemplateIdentifier(
            $this->getEmailTemplate()
        )->setTemplateOptions(
            [
                'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
            ]
        )->setTemplateVars(
            $vars
        )->setFrom(
            $this->getEmailSender()
        )->addTo(
            $receiverEmail,
            $receiverName
        );
        if ($copyTo && $copyMethod == 'bcc') {
            // Add bcc to customer email
            foreach ($copyTo as $email) {
                $this->_transportBuilder->addBcc($email);
            }
        }
        /** @var \Magento\Framework\Mail\TransportInterface $transport */
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        // Email copies are sent as separated emails if their copy method is 'copy'
        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $this->_transportBuilder->setTemplateIdentifier(
                    $this->getEmailTemplate()
                )->setTemplateOptions(
                    [
                        'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )->setTemplateVars(
                    $vars
                )->setFrom(
                    $this->getEmailSender()
                )->addTo(
                    $email
                )->getTransport()->sendMessage();
            }
        }

        return $this;
    }

    /**
     * Unserialize file_info and entity_attributes after load
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $fileInfo = $this->getFileInfo();
        if (trim($fileInfo)) {
            $this->setFileInfo($this->serializer->unserialize($fileInfo));
        }

        $attrsInfo = $this->getEntityAttributes();
        if ($attrsInfo !== null && trim($attrsInfo)) {
            $this->setEntityAttributes($this->serializer->unserialize($attrsInfo));
        }

        return parent::_afterLoad();
    }

    /**
     * Serialize file_info and entity_attributes arrays before save
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $fileInfo = $this->getFileInfo();
        $this->validateFileFormat();

        if (is_array($fileInfo) && $fileInfo) {
            $this->setFileInfo($this->serializer->serialize($fileInfo));
        }

        $attrsInfo = $this->getEntityAttributes();
        if (is_array($attrsInfo) && $attrsInfo) {
            $this->setEntityAttributes($this->serializer->serialize($attrsInfo));
        }

        return parent::beforeSave();
    }

    /**
     * Check if data has valid file format.
     *
     * @return void
     * @throws LocalizedException
     */
    private function validateFileFormat(): void
    {
        $fileInfo = $this->getFileInfo();
        if (!is_array($fileInfo)) {
            $fileInfo = $this->serializer->unserialize($fileInfo);
        }
        $fileFormats = $this->exportConfig->getFileFormats();
        if (isset($fileInfo['file_format']) && !in_array($fileInfo['file_format'], array_keys($fileFormats), true)) {
            throw new LocalizedException(__('Please correct the file format.'));
        }
    }

    /**
     * Add task to cron after save
     *
     * @return $this
     */
    public function afterSave()
    {
        if ($this->getStatus() == 1) {
            $this->_addCronTask();
        } else {
            $this->_dropCronTask();
        }
        return parent::afterSave();
    }

    /**
     * Delete cron task
     *
     * @return $this
     */
    public function afterDelete()
    {
        $this->_dropCronTask();
        return parent::afterDelete();
    }

    /**
     * Add operation to cron
     *
     * @throws LocalizedException
     * @return $this
     */
    protected function _addCronTask()
    {
        $frequency = $this->getFreq();
        $time = $this->getStartTime();
        if (!is_array($time)) {
            $time = explode(':', $time);
        }
        $cronExprArray = [
            (int)$time[1],
            (int)$time[0],
            $frequency == \Magento\Cron\Model\Config\Source\Frequency::CRON_MONTHLY ? '1' : '*',
            '*',
            $frequency == \Magento\Cron\Model\Config\Source\Frequency::CRON_WEEKLY ? '1' : '*',
        ];

        $cronExprString = join(' ', $cronExprArray);
        $exprPath = $this->getExprConfigPath();
        $modelPath = $this->getModelConfigPath();
        try {
            /** @var \Magento\Framework\App\Config\ValueInterface $exprValue */
            $exprValue = $this->_configValueFactory->create()->load($exprPath, 'path');
            $oldCronExprString = $exprValue->getValue();
            if ($oldCronExprString != $cronExprString) {
                $exprValue->setValue($cronExprString)->setPath($exprPath)->save();
                $this->_cacheManager->clean(['crontab']);
            }

            $this->_configValueFactory->create()->load(
                $modelPath,
                'path'
            )->setValue(
                self::CRON_MODEL
            )->setPath(
                $modelPath
            )->save();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new LocalizedException(
                __('We were unable to save the cron expression.')
            );
        }
        return $this;
    }

    /**
     * Remove cron task
     *
     * @throws LocalizedException
     * @return $this
     */
    protected function _dropCronTask()
    {
        try {
            $this->_configValueFactory->create()->load($this->getExprConfigPath(), 'path')->delete();
            $this->_configValueFactory->create()->load($this->getModelConfigPath(), 'path')->delete();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new LocalizedException(__('Unable to delete the cron task.'));
        }
        return $this;
    }

    /**
     * Get cron_expr config path
     *
     * @return string
     */
    public function getExprConfigPath()
    {
        return sprintf(self::CRON_STRING_PATH, $this->getId(), 'schedule/cron_expr');
    }

    /**
     * Get cron callback model config path
     *
     * @return string
     */
    public function getModelConfigPath()
    {
        return sprintf(self::CRON_STRING_PATH, $this->getId(), 'run/model');
    }

    /**
     * Load operation by cron job code.
     *
     * Operation id must present in job code.
     *
     * @param string $jobCode
     * @return $this
     * @throws LocalizedException
     */
    public function loadByJobCode($jobCode)
    {
        $idPos = strrpos($jobCode, '_');
        if ($idPos !== false) {
            $operationId = (int)substr($jobCode, $idPos + 1);
        }
        if (!isset($operationId) || !$operationId) {
            throw new LocalizedException(__('Please correct the cron job task'));
        }

        return $this->load($operationId);
    }

    /**
     * Run scheduled operation. If some error occurred email notification will be send
     *
     * @return bool
     */
    public function run()
    {
        $shouldBeLogged = false;
        $runDate = $this->_dateModel->date();
        $runDateTimestamp = $this->_dateModel->gmtTimestamp($runDate);

        $this->setLastRunDate($runDateTimestamp);

        $operation = $this->getInstance();
        $operation->setRunDate($runDateTimestamp);

        $result = false;
        try {
            $result = $operation->runSchedule($this);
        } catch (\Exception $e) {
            $operation->addLogComment($e->getMessage());
        }

        $logDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
        $filePath = $this->getHistoryFilePath();

        if ($logDirectory->isExist($logDirectory->getRelativePath($filePath))) {
            $filePath = __('File has been not created');
        }

        if (!$result || isset($e) && is_object($e)) {
            $operation->addLogComment(__('Something went wrong and the operation failed.'));
            $this->sendEmailNotification(
                [
                    'operationName' => $this->getName(),
                    'trace' => nl2br($operation->getFormatedLogTrace()),
                    'entity' => $this->getEntityType(),
                    'dateAndTime' => $runDate,
                    'fileName' => $filePath,
                ]
            );
            $shouldBeLogged = true;
        }

        if ($operation->getErrorAggregator() && $operation->getErrorAggregator()->getErrorsCount()) {
            $shouldBeLogged = true;
        }

        if ($shouldBeLogged) {
            $this->_logger->warning($operation->getFormatedLogTrace());
        }

        $this->setIsSuccess($result);
        $this->save();

        return $result;
    }

    /**
     * Get file based on "file_info" from server (ftp, local) and put to tmp directory
     *
     * @param \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface $operation
     * @throws LocalizedException
     * @return string full file path
     */
    public function getFileSource(
        \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface $operation
    ) {
        $fileInfo = $this->getFileInfo();
        if (empty($fileInfo['file_name']) || empty($fileInfo['file_path'])) {
            throw new LocalizedException(
                __('We can\'t read the file source because the file name is empty.')
            );
        }
        $operation->addLogComment(__('Connecting to server'));
        $operation->addLogComment(__('Reading import file'));

        $pathInfo = $this->file->getPathInfo($fileInfo['file_name']);
        $extension = $pathInfo['extension'] ?? '';
        $filePath = $fileInfo['file_name'];
        $filePath = rtrim($fileInfo['file_path'], '\\/') . '/' . $filePath;
        $tmpFile = DirectoryList::TMP . '/' .uniqid() . '.' . $extension;

        try {
            $tmpFilePath = $this->readData($filePath, $tmpFile);
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            throw new LocalizedException(__('We can\'t read the import file.'));
        }
        $operation->addLogComment(__('Save history file content "%1"', $this->getHistoryFilePath()));
        $this->_saveOperationHistory($tmpFilePath);
        return $tmpFilePath;
    }

    /**
     * Save/upload file to server (ftp, local)
     *
     * @param \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface $operation
     * @param string $fileContent
     * @return bool
     * @throws LocalizedException
     */
    public function saveFileSource(
        \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface $operation,
        $fileContent
    ) {
        $operation->addLogComment(__('Save history file content "%1"', $this->getHistoryFilePath()));
        $this->_saveOperationHistory($fileContent);

        $fileInfo = $this->getFileInfo();
        $fileName = $operation->getScheduledFileName() . '.' . $fileInfo['file_format'];
        try {
            $result = $this->writeData($fileInfo['file_path'] . '/' . $fileName, $fileContent);
        } catch (\Exception $e) {
            throw new LocalizedException(
                __(
                    'We couldn\'t write file "%1" to "%2" with the "%3" driver.',
                    $fileName,
                    $fileInfo['file_path'],
                    $fileInfo['server_type']
                )
            );
        }
        $operation->addLogComment(__('Save file content'));

        return $result;
    }

    /**
     * Write data to specific storage (FTP, local filesystem)
     *
     * @param string $filePath
     * @param string $fileContent
     * @return bool|int
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws LocalizedException
     */
    protected function writeData($filePath, $fileContent)
    {
        $this->validateAdapterType();
        $fileInfo = $this->getFileInfo();
        if (Data::FTP_STORAGE == $fileInfo['server_type']) {
            $this->ftpAdapter->open($this->_prepareIoConfiguration($fileInfo));
            $filePath = '/' . trim($filePath, '\\/');
            $result = $this->ftpAdapter->write($filePath, $fileContent);
        } else {
            $rootDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::ROOT);
            $result = $rootDirectory->writeFile($filePath, $fileContent);
        }

        return $result;
    }

    /**
     * Check if data has 'server_type' and it's valid
     *
     * @return void
     * @throws LocalizedException
     */
    protected function validateAdapterType(): void
    {
        $fileInfo = $this->getFileInfo();
        $availableTypes = $this->_operationFactory->create()->getServerTypesOptionArray();
        if (!isset($fileInfo['server_type'])
            || !$fileInfo['server_type']
            || !isset($availableTypes[$fileInfo['server_type']])
        ) {
            throw new LocalizedException(__('Please correct the server type.'));
        }
    }

    /**
     * Read data from specific storage (FTP, local filesystem)
     *
     * @param string $source
     * @param string $destination
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws LocalizedException
     */
    protected function readData($source, $destination)
    {
        $tmpDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);

        $this->validateAdapterType();
        $fileInfo = $this->getFileInfo();
        if (Data::FTP_STORAGE == $fileInfo['server_type']) {
            $this->ftpAdapter->open($this->_prepareIoConfiguration($fileInfo));
            $source = '/' . trim($source, '\\/');
            $result = $this->ftpAdapter->read($source, $tmpDirectory->getAbsolutePath($destination));
        } else {
            $rootDirectory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
            if (!$rootDirectory->isExist($source)) {
                throw new LocalizedException(__('Import path %1 not exists', $source));
            }
            $contents = $rootDirectory->readFile($rootDirectory->getRelativePath($source));
            $result = $tmpDirectory->writeFile($destination, $contents);
        }
        if (!$result) {
            throw new LocalizedException(__('We can\'t read the file.'));
        }

        return $tmpDirectory->getAbsolutePath($destination);
    }

    /**
     * Get operation instance by operation type and set specific data to it
     *
     * Supported import, export
     *
     * @throws LocalizedException
     * @return \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface
     */
    public function getInstance()
    {
        /** @var \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface $operation */
        $operation = $this->_schedOperFactory->create(
            'Magento\ScheduledImportExport\Model\\' . $this->string->upperCaseWords($this->getOperationType())
        );

        $operation->initialize($this);
        return $operation;
    }

    /**
     * Prepare data for server io driver initialization
     *
     * @param array $fileInfo
     * @return array Prepared configuration
     */
    protected function _prepareIoConfiguration($fileInfo)
    {
        $data = [];
        foreach ($fileInfo as $key => &$v) {
            $key = str_replace('file_', '', $key);
            $data[$key] = $v;
        }
        unset($data['format'], $data['server_type'], $data['name']);
        if (isset($data['mode'])) {
            $data['file_mode'] = $data['mode'];
            unset($data['mode']);
        }
        if (isset($data['host']) && strpos($data['host'], ':') !== false) {
            $tmp = explode(':', $data['host']);
            $data['port'] = array_pop($tmp);
            $data['host'] = join(':', $tmp);
        }

        return $data;
    }

    /**
     * Save operation file history.
     *
     * @param string $source
     * @return $this
     * @throws LocalizedException
     */
    protected function _saveOperationHistory($source)
    {
        $logDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
        $filePath = $logDirectory->getRelativePath($this->getHistoryFilePath());

        try {
            $logDirectory->writeFile($filePath, $source);
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            throw new LocalizedException(__('We can\'t save the file history file.'));
        }
        return $this;
    }

    /**
     * Get file path of history operation files
     *
     * @throws LocalizedException
     * @return string
     */
    public function getHistoryFilePath()
    {
        $logDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
        $dirPath = self::LOG_DIRECTORY . self::FILE_HISTORY_DIRECTORY . $this->_dateModel->date('Y/m/d') . '/';
        $logDirectory->create($dirPath);

        $fileName = join('_', [$this->_getRunTime(), $this->getOperationType(), $this->getEntityType()]);

        $fileInfo = $this->getFileInfo();
        if (isset($fileInfo['file_format'])) {
            $extension = $fileInfo['file_format'];
        } elseif (isset($fileInfo['file_name'])) {
            $pathInfo = $this->file->getPathInfo($fileInfo['file_name']);
            $extension = $pathInfo['extension'] ?? '';
        } else {
            throw new LocalizedException(__('Unknown file format'));
        }

        return $logDirectory->getAbsolutePath($dirPath . $fileName . '.' . $extension);
    }

    /**
     * Get current time
     *
     * @return string
     */
    protected function _getRunTime()
    {
        $runDate = $this->getLastRunDate() ? $this->getLastRunDate() : null;
        return $this->_dateModel->date('H-i', $runDate);
    }
}
