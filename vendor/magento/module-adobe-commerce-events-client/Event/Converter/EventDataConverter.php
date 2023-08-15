<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdobeCommerceEventsClient\Event\Converter;

use Exception;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class for converting data to event suitable format
 *
 * @api
 * @since 1.1.0
 */
class EventDataConverter
{
    private const MAX_DEPTH = 5;

    /**
     * Convert object or array of objects to array format
     *
     * @param mixed $objectOrArray
     * @return array
     * @throws Exception
     */
    public function convert($objectOrArray): array
    {
        if (is_object($objectOrArray)) {
            if (method_exists($objectOrArray, 'toArray')) {
                return $this->convertAndCleanData($objectOrArray->toArray());
            }

            throw new LocalizedException(
                __(sprintf('Object %s can not be converted to array', get_class($objectOrArray)))
            );
        }

        if (is_array($objectOrArray)) {
            return $this->convertArray($objectOrArray);
        }

        throw new LocalizedException(__('Wrong type of input argument'));
    }

    /**
     * Converts event data to the array.
     *
     * @param array $data
     * @return array
     */
    private function convertArray(array $data): array
    {
        foreach (['data_object', 'collection', 'object'] as $key) {
            if (isset($data[$key]) && method_exists($data[$key], 'toArray')) {
                return $this->convertAndCleanData($data[$key]->toArray());
            }
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $result[$key] = $this->convertAndCleanData($value->toArray());
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Clears array from the cached items.
     *
     * Convert objects to array if possible otherwise clean array data from such objects.
     * If the converted object is instance of Collection returns only it `items` after conversion.
     * Maximum depth is added to avoid recursion.
     *
     * @param array $data
     * @param int $depth
     * @return array
     */
    private function convertAndCleanData(array $data, int $depth = 1): array
    {
        foreach ($data as $key => $value) {
            if (strpos($key, '_cache') === 0) {
                unset($data[$key]);
            }

            if (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $conversionResult = $value->toArray();
                    if ($value instanceof Collection && isset($conversionResult['items'])) {
                        $data[$key] = $conversionResult['items'];
                    } else {
                        $data[$key] = $conversionResult;
                    }
                } else {
                    unset($data[$key]);
                }
            }

            if (is_array($value) && $depth < self::MAX_DEPTH) {
                $data[$key] = $this->convertAndCleanData($value, $depth + 1);
            }
        }

        return $data;
    }
}
