<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Update\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Staging update status
 */
class Status implements OptionSourceInterface
{
    /**#@+
     * Status values
     */
    const STATUS_ACTIVE = 1;

    const STATUS_UPCOMING = 2;

    /**#@-*/

    /**
     * Retrieve option array
     *
     * @return string[]
     */
    public static function getOptionArray()
    {
        return [self::STATUS_ACTIVE => __('Active'), self::STATUS_UPCOMING => __('Upcoming')];
    }

    /**
     * Retrieve option array with empty value
     *
     * @return string[]
     */
    public function toOptionArray()
    {
        $result = [];

        foreach (self::getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }
}
