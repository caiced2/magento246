<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RewardGraphQl\Model\Formatter\Customer;

use Magento\Customer\Model\Customer;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Reward\Model\Reward;

/**
 * Format Reward Points Field Output
 *
 * @api
 */
interface FormatterInterface
{
    /**
     * Format Reward Points Field Output
     *
     * @param Customer $customer
     * @param StoreInterface $store
     * @param Reward $rewardInstance
     * @return array
     */
    public function format(Customer $customer, StoreInterface $store, Reward $rewardInstance): array;
}
