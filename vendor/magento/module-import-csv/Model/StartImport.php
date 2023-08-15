<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportCsv\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\WriteFactory;
use Magento\ImportCsvApi\Api\Data\SourceDataInterface;
use Magento\ImportCsvApi\Api\StartImportInterface;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Source\CsvFactory;

/**
 * @inheritdoc
 */
class StartImport implements StartImportInterface
{

    /**
     * @var Import
     */
    private $import;

    /**
     * @var CsvFactory
     */
    private $csvFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var WriteFactory
     */
    private $writeFactory;

    /**
     * @param Import $import
     * @param CsvFactory $csvFactory
     * @param Filesystem $filesystem
     * @param WriteFactory $writeFactory
     */
    public function __construct(
        Import $import,
        CsvFactory $csvFactory,
        Filesystem $filesystem,
        WriteFactory $writeFactory
    ) {
        $this->import = $import;
        $this->csvFactory = $csvFactory;
        $this->filesystem = $filesystem;
        $this->writeFactory = $writeFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(
        SourceDataInterface $source
    ): array {
        $sourceAsArray = $this->getDataAsArray($source);
        $this->import->setData($sourceAsArray);
        unset($sourceAsArray);
        $errors = [];
        try {
            $importAdapter = $this->createImportAdapter($source->getCsvData(), $source->getImportFieldSeparator());
            $this->processValidationResult($this->import->validateSource($importAdapter), $errors);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $errors[] = $e->getMessage();
        } catch (\Exception $e) {
            $errors[] ='Sorry, but the data is invalid or the file is not uploaded.';
        }
        if ($errors) {
            return $errors;
        }
        $processedEntities = $this->import->getProcessedEntitiesCount();
        $errorAggregator = $this->import->getErrorAggregator();
        $errorAggregator->initValidationStrategy(
            $this->import->getData(Import::FIELD_NAME_VALIDATION_STRATEGY),
            $this->import->getData(Import::FIELD_NAME_ALLOWED_ERROR_COUNT)
        );
        try {
            $this->import->importSource();
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }
        if ($this->import->getErrorAggregator()->hasToBeTerminated()) {
            $errors[] ='Maximum error count has been reached or system error is occurred!';
        } else {
            $this->import->invalidateIndex();
        }
        if (!$errors) {
            return ["Entities Processed: " . $processedEntities];
        }
        return $errors;
    }

    /**
     * Converts the source data to an array for Import
     *
     * @param SourceDataInterface $sourceData
     */
    private function getDataAsArray(SourceDataInterface $sourceData): array
    {
        $array = [
            'entity' => $sourceData->getEntity(),
            'behavior' => $sourceData->getBehavior(),
            Import::FIELD_NAME_VALIDATION_STRATEGY => $sourceData->getValidationStrategy(),
            Import::FIELD_NAME_ALLOWED_ERROR_COUNT => $sourceData->getAllowedErrorCount(),
            Import::FIELD_FIELD_SEPARATOR => $sourceData->getImportFieldSeparator(),
        ];
        if (null !== $sourceData->getImportFieldSeparator()) {
            $array[Import::FIELD_FIELD_SEPARATOR] = $sourceData->getImportFieldSeparator();
        }
        if (null !== $sourceData->getImportMultipleValueSeparator()) {
            $array[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR] = $sourceData->getImportMultipleValueSeparator();
        }
        if (null !== $sourceData->getImportEmptyAttributeValueConstant()) {
            $array[Import::FIELD_EMPTY_ATTRIBUTE_VALUE_CONSTANT] = $sourceData->getImportEmptyAttributeValueConstant();
        }
        if (null !== $sourceData->getImportImagesFileDir()) {
            $array[Import::FIELD_NAME_IMG_FILE_DIR] = $sourceData->getImportImagesFileDir();
        }
        return $array;
    }

    /**
     * Base64 decodes data,  decompresses if gz compressed, stores in memory or temp file, and loads CSV adapter
     *
     * @param string $importData
     * @param ?string $delimiter
     * @return Import\AbstractSource
     */
    private function createImportAdapter(string $importData, ?string $delimiter)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $importData = base64_decode($importData);
        if (0 === strncmp("\x1f\x8b", $importData, 2)) { // gz's magic string
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $importData = gzdecode($importData);
        }
        $openedFile = $this->writeFactory->create('php://temp', '', 'w');
        $openedFile->write($importData);
        unset($importData);
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $parameters = ['directory' => $directory, 'file' => $openedFile];
        if (!empty($delimiter)) {
            $parameters['delimiter'] = $delimiter;
        }
        $adapter = $this->csvFactory->create($parameters);
        return $adapter;
    }

    /**
     * Process validation result and add required error or success messages to Result block
     *
     * @param bool $validationResult
     * @param array $errors
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processValidationResult($validationResult, &$errors)
    {
        $import = $this->import;
        $errorAggregator = $import->getErrorAggregator();

        if ($import->getProcessedRowsCount()) {
            if ($validationResult) {
                $this->addMessageForValidResult($errors);
            } else {
                $errors[] = 'Data validation failed. Please fix the following errors and upload the file again.';
                if ($errorAggregator->getErrorsCount()) {
                    $this->addMessageToSkipErrors($errors);
                }
            }
        } elseif ($errorAggregator->getErrorsCount()) {
            $this->collectErrors($errors);
        } else {
            $errors[] = 'This file is empty. Please try another one.';
            return;
        }

        if ($this->import->getData(Import::FIELD_NAME_VALIDATION_STRATEGY) === 'validation-skip-errors'
            && $errorAggregator->getErrorsCount() <= $errorAggregator->getAllowedErrorsCount()) {
            $errorAggregator->clear();
        } elseif (!$errors) {
            $this->collectErrors($errors);
        }
    }

    /**
     * Add Message for Valid Result
     *
     * @param array $errors
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addMessageForValidResult(&$errors)
    {
        if (!$this->import->isImportAllowed()) {
            $errors[] =__('The file is valid, but we can\'t import it for some reason.');
        }
    }

    /**
     * Collect errors and add error messages
     *
     * Get all errors from Error Aggregator and add appropriated error messages
     *
     * @param array $errors
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function collectErrors(&$errors)
    {
        $processedErrors = $this->import->getErrorAggregator()->getAllErrors();
        foreach ($processedErrors as $error) {
            $errors[] = 'Row ' . ($error->getRowNumber() + 1) . ': ' . $error->getErrorMessage();
        }
    }

    /**
     * Add error message to Result block and allow 'Import' button
     *
     * If validation strategy is equal to 'validation-skip-errors' and validation error limit is not exceeded,
     * then add error message and allow 'Import' button.
     *
     * @param array $errors
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addMessageToSkipErrors(&$errors)
    {
        $import = $this->import;
        if ($import->getErrorAggregator()->hasFatalExceptions()) {
            $errors[] = 'Please fix errors and re-upload file';
        }
    }
}
