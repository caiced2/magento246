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
 * List of methods
 */
class Method implements ArrayInterface
{
    /**
     * Methods as array
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => Config::ENVIRONMENT_SANDBOX, 'label' => __('Sandbox')],
            ['value' => Config::ENVIRONMENT_PRODUCTION, 'label' => __('Production')]
        ];
    }
}
