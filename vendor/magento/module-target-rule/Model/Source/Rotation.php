<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Source;

/**
 * List available rotation options
 */
class Rotation implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Weighted random
     *
     * The lowest priority is assigned the highest weight. Selected products have the highest weight
     */
    public const ROTATION_WEIGHTED_RANDOM = 2;
    /**
     * Get data for Rotation mode selector
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            \Magento\TargetRule\Model\Rule::ROTATION_NONE => __('By Priority, Then by ID'),
            \Magento\TargetRule\Model\Rule::ROTATION_SHUFFLE => __('By Priority, Then Random'),
            self::ROTATION_WEIGHTED_RANDOM => __('Weighted Random'),
        ];
    }
}
