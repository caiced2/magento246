<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer;

use Magento\Framework\Event\ObserverInterface;

class SetRewardPointsBalanceToRefund implements ObserverInterface
{
    /**
     * Set reward points balance to refund before creditmemo register
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $input = $observer->getEvent()->getInput();
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $creditmemo->setRewardPointsBalanceRefundFlag(false);
        if (isset($input['refund_reward_points'], $input['refund_reward_points_enable'])
            && $input['refund_reward_points_enable']
        ) {
            $balance = (int)$input['refund_reward_points'];
            $balance = min($creditmemo->getRewardPointsBalance(), $balance);
            if ($balance) {
                $creditmemo->setRewardPointsBalanceRefund(round($balance));
            }
            $creditmemo->setRewardPointsBalanceRefundFlag(true);
        }
        return $this;
    }
}
