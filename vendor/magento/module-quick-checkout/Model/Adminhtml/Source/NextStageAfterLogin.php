<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\QuickCheckout\Model\Config;

/**
 * List of next stages after login
 */
class NextStageAfterLogin implements ArrayInterface
{
    /**
     * Next stages after login as array
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => Config::STAGE_PAYMENT, 'label' => __('Payment')],
            ['value' => Config::STAGE_SHIPPING, 'label' => __('Shipping')]
        ];
    }
}
