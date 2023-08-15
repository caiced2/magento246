<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Source\Points;

/**
 * Source model for list of Expiry Calculation algorithms
 * @codeCoverageIgnore
 */
class ExpiryCalculation implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Expiry calculation options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'static', 'label' => __('Static')],
            ['value' => 'dynamic', 'label' => __('Dynamic')]
        ];
    }
}
