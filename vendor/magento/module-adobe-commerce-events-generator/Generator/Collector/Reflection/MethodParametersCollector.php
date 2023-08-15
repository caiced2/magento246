<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Generator\Collector\Reflection;

use ReflectionMethod;

/**
 * Collects a list of parameters for the given method
 */
class MethodParametersCollector
{
    /**
     * Collects a list of parameters with their type and defaults for the given method
     *
     * @param ReflectionMethod $reflectionMethod
     * @return array
     */
    public function collect(ReflectionMethod $reflectionMethod): array
    {
        $params = [];

        foreach ($reflectionMethod->getParameters() as $param) {
            $methodParams = [
                'type' => $param->getType() ? $param->getType()->getName() : null,
                'name' => $param->getName(),
                'isDefaultValueAvailable' => false,
            ];

            if ($param->isDefaultValueAvailable()) {
                $methodParams['isDefaultValueAvailable'] = true;
                $methodParams['defaultValue'] = $this->formatDefaultValue($param->getDefaultValue());
            }

            $params[] = $methodParams;
        }

        return $params;
    }

    /**
     * Convert default value to appropriate string format
     *
     * @param mixed $defaultValue
     * @return string
     */
    private function formatDefaultValue($defaultValue): string
    {
        if (is_string($defaultValue)) {
            return '\'' . $defaultValue . '\'';
        }

        if (is_array($defaultValue)) {
            return '[' . implode(', ', $defaultValue) . ']';
        }

        if ($defaultValue === null) {
            return 'null';
        }

        if (is_bool($defaultValue)) {
            return $defaultValue ? 'true' : 'false';
        }

        return (string)$defaultValue;
    }
}
