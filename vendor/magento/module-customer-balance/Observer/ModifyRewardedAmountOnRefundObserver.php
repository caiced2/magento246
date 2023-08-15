<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;

class ModifyRewardedAmountOnRefundObserver implements ObserverInterface
{
    /**
     * Modify the amount of invoiced funds for which reward points should not be voided after refund.
     * Prevent voiding of reward points for amount returned to store credit.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();

        $rewardedAmountAfterRefund = $creditMemo->getRewardedAmountAfterRefund();

        $customerBalanceTotalRefunded = $order->getBsCustomerBalTotalRefunded();
        $rewardedAmountRefunded = $order->getBaseTotalRefunded() - $order->getBaseTaxRefunded()
            - $order->getBaseShippingRefunded();
        if ($customerBalanceTotalRefunded > $rewardedAmountRefunded) {
            $rewardedAmountAfterRefund += $rewardedAmountRefunded;
        } else {
            $rewardedAmountAfterRefund += $customerBalanceTotalRefunded;
        }

        $creditMemo->setRewardedAmountAfterRefund($rewardedAmountAfterRefund);
    }
}
