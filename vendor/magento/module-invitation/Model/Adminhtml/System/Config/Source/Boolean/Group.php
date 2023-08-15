<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Invitation source for reffered customer group system configuration
 */
namespace Magento\Invitation\Model\Adminhtml\System\Config\Source\Boolean;

class Group implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return the option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [1 => __('Same as Inviter'), 0 => __('Default Customer Group from System Configuration')];
    }
}
