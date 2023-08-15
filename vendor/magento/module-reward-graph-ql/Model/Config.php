<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RewardGraphQl\Model;

use Magento\Reward\Helper\Data as RewardHelper;

/**
 * Utility for obtaining Reward points configuration details
 */
class Config
{
    /**
     * @var RewardHelper
     */
    private $rewardHelper;

    /**
     * @param RewardHelper $rewardHelper
     */
    public function __construct(
        RewardHelper $rewardHelper
    ) {
        $this->rewardHelper = $rewardHelper;
    }

    /**
     * Check if Reward points feature is disabled for the given website
     *
     * @param int $websiteId
     * @return bool
     */
    public function isDisabled(int $websiteId): bool
    {
        if (!$this->rewardHelper->getGeneralConfig('is_enabled')
            || !$this->rewardHelper->getGeneralConfig('is_enabled_on_front', $websiteId)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if Customers are allowed to see Reward Points history
     *
     * @param int $websiteId
     * @return bool
     */
    public function customersMaySeeHistory(int $websiteId): bool
    {
        if ($this->rewardHelper->getGeneralConfig('publish_history', $websiteId)) {
            return true;
        }

        return false;
    }

    /**
     * Obtain expiration details
     *
     * @return array
     */
    public function getExpirationDetails(): array
    {
        return $this->rewardHelper->getExpiryConfig();
    }
}
