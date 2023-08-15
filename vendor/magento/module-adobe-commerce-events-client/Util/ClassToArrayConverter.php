<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Util;

use ReflectionException;

/**
 * Converts class to the array by converting getter methods to appropriate values
 */
class ClassToArrayConverter
{
    public const NESTED_LEVEL = 2;

    /**
     * @var ReflectionHelper
     */
    private ReflectionHelper $reflectionHelper;

    /**
     * @param ReflectionHelper $reflectionHelper
     */
    public function __construct(ReflectionHelper $reflectionHelper)
    {
        $this->reflectionHelper = $reflectionHelper;
    }

    /**
     * Converts class to the array by converting its `getter` and `is*` methods to appropriate values
     *
     * @param string $className
     * @param int $nestedLevel
     * @param int $level
     * @return array
     */
    public function convert(string $className, int $nestedLevel = self::NESTED_LEVEL, int $level = 1): array
    {
        $result = [];

        try {
            $objectProperties = $this->reflectionHelper->getObjectProperties($className);
        } catch (ReflectionException $e) {
            return [$className];
        }

        foreach ($objectProperties as $prop) {
            if ($this->reflectionHelper->isSimple($prop['type']) || $level >= $nestedLevel) {
                $result[$prop['name']] = $prop['type'];
                continue;
            }

            if (!$this->reflectionHelper->isArray($prop['type'])) {
                $result[$prop['name']] = $this->convert($prop['type'], $nestedLevel, $level + 1);
            } else {
                $result[$prop['name']] = [
                    $this->convert($this->reflectionHelper->arrayTypeToSingle($prop['type']), $nestedLevel, $level + 1)
                ];
            }
        }

        return $result;
    }
}
