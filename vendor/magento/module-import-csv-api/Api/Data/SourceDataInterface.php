<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportCsvApi\Api\Data;

/**
 * Describes how to retrieve data from data source
 *
 * @api
 * @since 100.0.2
 */
interface SourceDataInterface
{
    /**
     * Get Entity
     *
     * @return string
     */
    public function getEntity(): string;

    /**
     * Get Behavior
     *
     * @return string
     */
    public function getBehavior(): string;

    /**
     * Get Validation Strategy
     *
     * @return string
     */
    public function getValidationStrategy(): string;

    /**
     * Get Allowed Error Count
     *
     * @return string
     */
    public function getAllowedErrorCount(): string;

    /**
     * Set Entity
     *
     * @param string $entity
     * @return void
     */
    public function setEntity(string $entity);

    /**
     * Set Behavior
     *
     * @param string $behavior
     * @return void
     */
    public function setBehavior(string $behavior);

    /**
     * Set Validation Strategy
     *
     * @param string $validationStrategy
     * @return void
     */
    public function setValidationStrategy(string $validationStrategy);

    /**
     *  Set Allowed Error Count
     *
     * @param string $allowedErrorCount
     * @return void
     */
    public function setAllowedErrorCount(string $allowedErrorCount);

    /**
     *  Set CSV data as string
     *
     * @param string $csvData
     * @return void
     */
    public function setCsvData(string $csvData);

    /**
     *  Set CSV data as string
     *
     * @return string
     */
    public function getCsvData();

    /**
     *  Set Import's Field Separator for CSV
     *
     * @param string $separator
     * @return void
     */
    public function setImportFieldSeparator(?string $separator = null);

    /**
     *  Get Import's Field Separator for CSV
     *
     * @return string|null
     */
    public function getImportFieldSeparator() : ?string;

    /**
     *  Set Import's Multiple Value Field Separator for CSV
     *
     * @param string|null $separator
     * @return void
     */
    public function setImportMultipleValueSeparator(?string $separator = null);

    /**
     *  Get Import's Multiple Value Field Separator for CSV
     *
     * @return string|null
     */
    public function getImportMultipleValueSeparator() : ?string;

    /**
     *  Set Import's Empty Attribute Value Constant
     *
     * @param string|null $constant
     * @return void
     */
    public function setImportEmptyAttributeValueConstant(?string $constant = null);

    /**
     *  Get Import's Empty Attribute Value Constant
     *
     * @return string|null
     */
    public function getImportEmptyAttributeValueConstant() : ?string;

    /**
     *  Set Import's Images File Directory
     *
     * @param string|null $dir
     * @return void
     */
    public function setImportImagesFileDir(?string $dir = null);

    /**
     *  Get Import's Images File Directory
     *
     * @return string|null
     */
    public function getImportImagesFileDir() : ?string;
}
