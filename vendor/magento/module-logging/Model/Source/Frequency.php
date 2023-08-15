<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Model\Source;

/**
 * Source model for logging frequency
 *
 * @api
 * @since 100.0.2
 */
class Frequency implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get options as array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('Daily')],
            ['value' => 7, 'label' => __('Weekly')],
            ['value' => 30, 'label' => __('Monthly')]
        ];
    }
}
