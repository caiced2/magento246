<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Setup;

use Magento\Framework\DB\DataConverter\DataConversionException;
use Magento\Framework\DB\DataConverter\SerializedToJson;

/**
 * This Converter allows to work with Php-Serialized strings that can contain Objects
 * It converts data from Php-Serialized to JSON format.
 */
class ObjectConverter extends SerializedToJson
{
    /**
     * Convert object from serialized to JSON format
     *
     * @param string $value
     * @return string
     * @throws DataConversionException
     */
    public function convert($value)
    {
        if ($this->isValidJsonValue($value)) {
            return $value;
        }
        try {
            set_error_handler(function ($errorNumber, $errorString) {
                throw new DataConversionException($errorString, $errorNumber);
            });
            // We have to use unserialize here for objects conversion.
            // phpcs:ignore Magento2.Security.InsecureFunction
            $value = unserialize($value);
        } catch (\Throwable $throwable) {
            throw new DataConversionException($throwable->getMessage());
        } finally {
            restore_error_handler();
        }
        return parent::encodeJson($value);
    }
}
