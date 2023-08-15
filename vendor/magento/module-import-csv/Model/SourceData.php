<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportCsv\Model;

use Magento\ImportCsvApi\Api\Data\SourceDataInterface;

class SourceData implements SourceDataInterface
{

    /**
     * @var string
     */
    private $entity;

    /**
     * @var string
     */
    private $behavior;

    /**
     * @var string
     */
    private $validationStrategy;

    /**
     * @var string
     */
    private $allowedErrorCount;

    /**
     * @var string
     */
    private $csvData;

    /**
     * @var ?string
     */
    private $importFieldSeparator;

    /**
     * @var ?string
     */
    private $importMultipleValueSeparator;

    /**
     * @var ?string
     */
    private $importEmptyAttributeValueConstant;

    /**
     * @var ?string
     */
    private $importImagesFileDir;

    /**
     * @inheritdoc
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @inheritdoc
     */
    public function getBehavior(): string
    {
        return $this->behavior;
    }

    /**
     * @inheritdoc
     */
    public function getValidationStrategy(): string
    {
        return $this->validationStrategy;
    }

    /**
     * @inheritdoc
     */
    public function getAllowedErrorCount(): string
    {
        return $this->allowedErrorCount;
    }

    /**
     * @inheritDoc
     */
    public function getCsvData()
    {
        return $this->csvData;
    }

    /**
     * @inheritDoc
     */
    public function setEntity(string $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @inheritDoc
     */
    public function setBehavior(string $behavior)
    {
        $this->behavior = $behavior;
    }

    /**
     * @inheritDoc
     */
    public function setValidationStrategy(string $validationStrategy)
    {
        $this->validationStrategy = $validationStrategy;
    }

    /**
     * @inheritDoc
     */
    public function setAllowedErrorCount(string $allowedErrorCount)
    {
        $this->allowedErrorCount = $allowedErrorCount;
    }

    /**
     * @inheritDoc
     */
    public function setCsvData($csvData)
    {
        $this->csvData = $csvData;
    }

    /**
     * @inheritDoc
     */
    public function setImportFieldSeparator(?string $separator = null)
    {
        $this->importFieldSeparator = $separator;
    }

    /**
     * @inheritDoc
     */
    public function getImportFieldSeparator(): ?string
    {
        return $this->importFieldSeparator;
    }

    /**
     * @inheritDoc
     */
    public function setImportMultipleValueSeparator(?string $separator = null)
    {
        $this->importMultipleValueSeparator = $separator;
    }

    /**
     * @inheritDoc
     */
    public function getImportMultipleValueSeparator(): ?string
    {
        return $this->importMultipleValueSeparator;
    }

    /**
     * @inheritDoc
     */
    public function setImportEmptyAttributeValueConstant(?string $constant = null)
    {
        $this->importEmptyAttributeValueConstant = $constant;
    }

    /**
     * @inheritDoc
     */
    public function getImportEmptyAttributeValueConstant(): ?string
    {
        return $this->importEmptyAttributeValueConstant;
    }

    /**
     * @inheritDoc
     */
    public function setImportImagesFileDir(?string $dir = null)
    {
        $this->importImagesFileDir = $dir;
    }

    /**
     * @inheritDoc
     */
    public function getImportImagesFileDir(): ?string
    {
        return $this->importImagesFileDir;
    }
}
