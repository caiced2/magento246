<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RewardGraphQl\Model\Formatter\Customer;

use Magento\Customer\Model\Customer;
use Magento\Reward\Model\Reward;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Format Customer Reward Points Balance
 */
class Balance implements FormatterInterface
{
    /**
     * @inheritdoc
     */
    public function format(Customer $customer, StoreInterface $store, Reward $rewardInstance): array
    {
        return [
            'money' => [
                'currency' => $store->getCurrentCurrency()->getCode(),
                'value' => $rewardInstance->getCurrencyAmount()
            ],
            'points' => $rewardInstance->getPointsBalance()
        ];
    }
}
