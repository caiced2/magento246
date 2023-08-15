<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Model\Rule\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsEnabled
 */
class IsEnabled implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = [
            \Magento\Banner\Model\Banner::STATUS_ENABLED => __('Active'),
            \Magento\Banner\Model\Banner::STATUS_DISABLED => __('Inactive'),
        ];
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
