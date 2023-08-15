<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RewardGraphQl\Model\Formatter\Customer;

use Magento\Customer\Model\Customer;
use Magento\Framework\GraphQl\Query\EnumLookup;
use Magento\Reward\Model\Reward;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Format Customer Reward Points Subscription Statuses
 */
class SubscriptionStatuses implements FormatterInterface
{
    /**
     * @var string
     */
    private $rewardPointsSubscriptionStatusesEnum = 'RewardPointsSubscriptionStatusesEnum';

    /**
     * @var EnumLookup
     */
    private $enumLookup;

    /**
     * @param EnumLookup $enumLookup
     */
    public function __construct(
        EnumLookup $enumLookup
    ) {
        $this->enumLookup = $enumLookup;
    }

    /**
     * @inheritdoc
     */
    public function format(Customer $customer, StoreInterface $store, Reward $rewardInstance): array
    {
        $balanceUpdatesStatus = $this->enumLookup->getEnumValueFromField(
            $this->rewardPointsSubscriptionStatusesEnum,
            $customer->getRewardUpdateNotification() ?? '0'
        );
        $pointsExpirationNotificationsStatus = $this->enumLookup->getEnumValueFromField(
            $this->rewardPointsSubscriptionStatusesEnum,
            $customer->getRewardWarningNotification() ?? '0'
        );

        return [
            'balance_updates' => $balanceUpdatesStatus,
            'points_expiration_notifications' => $pointsExpirationNotificationsStatus
        ];
    }
}
