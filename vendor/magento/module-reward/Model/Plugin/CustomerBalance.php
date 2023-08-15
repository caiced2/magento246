<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Model\Plugin;

use Magento\CustomerBalance\Model\Creditmemo\Balance;
use Magento\Framework\Exception\LocalizedException;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Plugin checks if refunded customer balance and reward points do not exceed available balance.
 */
class CustomerBalance
{
    /**
     * @var RewardFactory
     */
    private $rewardFactory;

    /**
     * @param RewardFactory $rewardFactory
     */
    public function __construct(
        RewardFactory $rewardFactory
    ) {
        $this->rewardFactory = $rewardFactory;
    }

    /**
     * Before customer balance save processing.
     *
     * @param Balance $subject
     * @param Creditmemo $creditmemo
     * @return void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        Balance $subject,
        Creditmemo $creditmemo
    ) {
        if (!$this->isBalanceAvailable($creditmemo)) {
            throw new LocalizedException(__('You can\'t use more store credit than the order amount.'));
        }
    }

    /**
     * Checks if refunded amount does not exceed available balance.
     *
     * @param Creditmemo $creditmemo
     * @return bool
     */
    private function isBalanceAvailable(Creditmemo $creditmemo): bool
    {
        $order = $creditmemo->getOrder();
        /**@var Reward $reward */
        $reward = $this->rewardFactory->create();
        $reward->setStoreId($order->getStoreId());
        $reward->setCustomerId($order->getCustomerId());
        $refundedToRewardPoints = $creditmemo->getRewardPointsBalanceRefund();
        $refundedToCustomerBalance = $reward->getPointsEquivalent(
            (float) $creditmemo->getBsCustomerBalTotalRefunded()
        );

        // As Reward Points is rounded value but max allowed Customer Balance calculated with raw totals,
        // we need to use some delta to validate allowed balance
        $rewardPointsCeilCompensation = 1;
        $availableBalance = $reward->getPointsEquivalent(
            (float) $creditmemo->getBaseCustomerBalanceReturnMax() + $rewardPointsCeilCompensation
        );

        return $refundedToRewardPoints + $refundedToCustomerBalance <= $availableBalance;
    }
}
