<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Attributes;

/**
 * Class responsible for formatting data for output in grid cells
 */
class DataFormatter
{
    /**
     * Prepare value for model column
     *
     * @param string $className
     * @return string
     */
    public function prepareModelValue($className)
    {
        if ($className) {
            return $className . "\n" . '{' . str_replace('\\', '/', $className) . '.php}';
        }
        return '';
    }
}
