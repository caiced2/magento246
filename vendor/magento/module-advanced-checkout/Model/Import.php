<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

/**
 * Import data from file
 */
class Import extends \Magento\Framework\DataObject
{
    /**
     * Form field name
     */
    public const FIELD_NAME_SOURCE_FILE = 'sku_file';

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $random;

    /**
     * Uploaded file name
     *
     * @var string
     */
    protected $_uploadedFile = '';

    /**
     * Allowed file name extensions to upload
     *
     * @var string[]
     */
    protected $_allowedExtensions = ['csv'];

    /**
     * @var \Magento\AdvancedCheckout\Helper\Data
     */
    protected $_checkoutData = null;

    /**
     * File uploader factory
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploaderFactory = null;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $varDirectory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * The upload path
     *
     * @var string
     */
    protected $uploadPath = 'import_sku/';

    /**
     * @param \Magento\AdvancedCheckout\Helper\Data $checkoutData
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param array $data
     * @param \Magento\Framework\Math\Random $random
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\AdvancedCheckout\Helper\Data $checkoutData,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        array $data = [],
        \Magento\Framework\Math\Random $random = null
    ) {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        register_shutdown_function([$this, 'destruct']);
        $this->_checkoutData = $checkoutData;
        parent::__construct($data);
        $this->_uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->random = $random ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Math\Random::class);
    }

    /**
     * Return instance of directory with write permissions
     *
     * @return \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected function getVarDirectory()
    {
        if (empty($this->varDirectory)) {
            $this->varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        }
        return $this->varDirectory;
    }

    /**
     * Destructor, removes uploaded file
     *
     * @return void
     */
    public function destruct()
    {
        if (!empty($this->_uploadedFile)) {
            $this->getVarDirectory()->delete($this->_uploadedFile);
        }
    }

    /**
     * Upload file
     *
     * @throws LocalizedException
     * @return void
     */
    public function uploadFile()
    {
        /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
        $uploader = $this->_uploaderFactory->create(['fileId' => self::FIELD_NAME_SOURCE_FILE]);
        $uploader->setAllowedExtensions($this->_allowedExtensions);
        $uploader->skipDbProcessing(true);
        $fileExtension = $uploader->getFileExtension();
        $newFileName = $this->random->getRandomString(32) . '.' . $fileExtension;
        if (!$uploader->checkAllowedExtension($fileExtension)) {
            throw new LocalizedException($this->_getFileTypeMessageText());
        }

        try {
            $result = $uploader->save($this->getVarDirectory()->getAbsolutePath($this->uploadPath), $newFileName);
        } catch (\Exception $e) {
            throw new LocalizedException($this->_checkoutData->getFileGeneralErrorText());
        }

        if ($result === false) {
            throw new LocalizedException($this->_checkoutData->getFileGeneralErrorText());
        }
        $this->_uploadedFile = $this->getVarDirectory()->getRelativePath($result['path'] . $result['file']);
    }

    /**
     * Get rows from file
     *
     * @return array
     * @throws LocalizedException
     */
    public function getRows()
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $extension = pathinfo($this->_uploadedFile, PATHINFO_EXTENSION);
        $method = $this->_getMethodByExtension(strtolower($extension));
        if (!empty($method) && is_callable([$this, $method])) {
            return $this->{$method}();
        }

        throw new LocalizedException($this->_getFileTypeMessageText());
    }

    /**
     * Get rows from CSV file
     *
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getDataFromCsv()
    {
        if (!$this->_uploadedFile || !$this->getVarDirectory()->isExist($this->_uploadedFile)) {
            throw new LocalizedException($this->_checkoutData->getFileGeneralErrorText());
        }

        $csvData = [];

        try {
            $fileHandler = $this->getVarDirectory()->openFile($this->_uploadedFile, 'r');
            if ($fileHandler) {
                $colNames = $fileHandler->readCsv();

                foreach ($colNames as &$colName) {
                    $colName = $colName !== null ? trim($colName) : '';
                }

                $requiredColumns = ['sku', 'qty'];
                $requiredColumnsPositions = [];

                foreach ($requiredColumns as $columnName) {
                    $found = array_search($columnName, $colNames);
                    if (false !== $found) {
                        $requiredColumnsPositions[] = $found;
                    } else {
                        throw new LocalizedException($this->_checkoutData->getSkuEmptyDataMessageText());
                    }
                }

                while (($currentRow = $fileHandler->readCsv()) !== false) {
                    $csvDataRow = ['qty' => ''];
                    foreach ($requiredColumnsPositions as $index) {
                        if (isset($currentRow[$index])) {
                            $csvDataRow[$colNames[$index]] = trim($currentRow[$index]);
                        }
                    }
                    if (isset($csvDataRow['sku']) && $csvDataRow['sku'] !== '') {
                        $csvData[] = $csvDataRow;
                    }
                }
                $fileHandler->close();
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__("The file is corrupt and can't be used."));
        }
        return $csvData;
    }

    /**
     * Get Method to load data by file extension
     *
     * @param string $extension
     * @return string
     * @throws LocalizedException
     */
    protected function _getMethodByExtension($extension)
    {
        foreach ($this->_allowedExtensions as $allowedExtension) {
            if ($allowedExtension == $extension) {
                return 'getDataFrom' . ucfirst($allowedExtension);
            }
        }

        throw new LocalizedException($this->_getFileTypeMessageText());
    }

    /**
     * Get message text of wrong file type error
     *
     * @codeCoverageIgnore
     * @return \Magento\Framework\Phrase
     */
    protected function _getFileTypeMessageText()
    {
        return __('Please upload the file in .csv format.');
    }
}
