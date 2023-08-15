<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * List of networks
 */
class Network implements ArrayInterface
{
    public const BOLT = 'bolt';
    public const BOLT_PLUS_MERCHANT = 'bolt_plus_merchant';

    /**
     * Methods as array
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::BOLT, 'label' => __('Bolt')],
            ['value' => self::BOLT_PLUS_MERCHANT, 'label' => __('Bolt + Merchant')]
        ];
    }
}
