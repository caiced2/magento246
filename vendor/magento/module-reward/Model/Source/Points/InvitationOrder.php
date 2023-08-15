<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Source\Points;

/**
 * Source model for Acquiring frequency when Order processed after Invitation
 * @codeCoverageIgnore
 */
class InvitationOrder implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Invitation order options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => '*', 'label' => __('Each')], ['value' => '1', 'label' => __('First')]];
    }
}
