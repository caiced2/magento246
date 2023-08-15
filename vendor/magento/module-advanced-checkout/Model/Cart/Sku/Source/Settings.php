<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Enable Order by SKU in 'My Account' options source
 *
 */
namespace Magento\AdvancedCheckout\Model\Cart\Sku\Source;

class Settings implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Enable Order by SKU in 'My Account' options values
     */
    const NO_VALUE = 0;

    const YES_VALUE = 1;

    const YES_SPECIFIED_GROUPS_VALUE = 2;

    /**
     * Get options as array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Yes, for Specified Customer Groups'), 'value' => self::YES_SPECIFIED_GROUPS_VALUE],
            ['label' => __('Yes, for Everyone'), 'value' => self::YES_VALUE],
            ['label' => __('No'), 'value' => self::NO_VALUE]
        ];
    }
}
