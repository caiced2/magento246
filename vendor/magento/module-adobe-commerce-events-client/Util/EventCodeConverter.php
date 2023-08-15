<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Util;

/**
 * Converts event codes to FQCN class
 *
 * @api
 * @since 1.1.0
 */
class EventCodeConverter
{
    /**
     * Convert event code to FQCN class name and removes method name from the path.
     * For example:
     * plugin.magento.theme.api.design_config_repository.save => Magento\Theme\Api\DesignConfigRepository
     *
     * @param string $eventCode
     * @return string
     */
    public function convertToFqcn(string $eventCode): string
    {
        $eventCodeParts = array_slice(explode('.', $eventCode), 0, -1);
        if (in_array($eventCodeParts[0], ['plugin', 'observer'])) {
            $eventCodeParts = array_slice($eventCodeParts, 1);
        }

        $class = '';
        foreach ($eventCodeParts as $part) {
            $class .= $this->underscoresToCamelCase($part) . '\\';
        }

        return rtrim($class, '\\');
    }

    /**
     * Converts class name to the event name.
     *
     * @param string $className
     * @param string $methodName
     * @return string
     */
    public function convertToEventName(string $className, string $methodName): string
    {
        $eventName = '';
        $namespaceParts = explode('\\', preg_replace('/Interface$/', '', $className));
        foreach ($namespaceParts as $namespacePart) {
            $eventName .= $this->convertCamelCases($namespacePart) . '.';
        }

        return $eventName . $this->convertCamelCases($methodName);
    }

    /**
     * Extract method name from event code.
     *
     * @param string $eventCode
     * @return string
     */
    public function extractMethodName(string $eventCode): string
    {
        $eventCodeParts = explode('.', $eventCode);

        return lcfirst($this->underscoresToCamelCase(end($eventCodeParts)));
    }

    /**
     * Converts string with underscores to camel case format.
     *
     * @param string $str
     * @return string
     */
    private function underscoresToCamelCase(string $str): string
    {
        return implode('', array_map('ucfirst', explode('_', $str)));
    }

    /**
     * Convert camel case to lowercase with underscores.
     *
     * CamelCaseFormat => camel_case_format
     *
     * @param string $string
     * @return string
     */
    private function convertCamelCases(string $string): string
    {
        return implode('_', array_map('strtolower', preg_split('/(?=[A-Z])/', $string, -1, PREG_SPLIT_NO_EMPTY)));
    }
}
