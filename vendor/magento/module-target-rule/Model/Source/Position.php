<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Source;

class Position implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get data for Position behavior selector
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            \Magento\TargetRule\Model\Rule::BOTH_SELECTED_AND_RULE_BASED => __('Both Selected and Rule-Based'),
            \Magento\TargetRule\Model\Rule::SELECTED_ONLY => __('Selected Only'),
            \Magento\TargetRule\Model\Rule::RULE_BASED_ONLY => __('Rule-Based Only')
        ];
    }
}
