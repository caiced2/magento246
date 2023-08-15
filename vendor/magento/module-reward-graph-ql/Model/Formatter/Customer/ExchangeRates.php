<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RewardGraphQl\Model\Formatter\Customer;

use Magento\Customer\Model\Customer;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\Reward\Rate;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Format Store Exchange Rates for a given Reward Instance
 */
class ExchangeRates implements FormatterInterface
{
    /**
     * @inheritdoc
     */
    public function format(Customer $customer, StoreInterface $store, Reward $rewardInstance): array
    {
        /** @var Rate $rateToPoints */
        $rateToPoints = $rewardInstance->getRateToPoints();
        /** @var Rate $rateToCurrency */
        $rateToCurrency = $rewardInstance->getRateToCurrency();

        return [
            'earning' => [
                'points' => (float)$rateToPoints->getPoints(),
                'currency_amount' => (float)$rateToPoints->getCurrencyAmount()
            ],
            'redemption' => [
                'points' => (float)$rateToCurrency->getPoints(),
                'currency_amount' => (float)$rateToCurrency->getCurrencyAmount()
            ],
        ];
    }
}
