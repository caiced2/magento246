<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Sales\Model\Order;

class OrderCompleted implements ObserverInterface
{
    /**
     * Reward factory
     *
     * @var RewardFactory
     */
    protected $_rewardFactory;

    /**
     * Reward helper
     *
     * @var Data
     */
    protected $_rewardData;

    /**
     * @param Data $rewardData
     * @param RewardFactory $rewardFactory
     */
    public function __construct(
        Data $rewardData,
        RewardFactory $rewardFactory
    ) {
        $this->_rewardData = $rewardData;
        $this->_rewardFactory = $rewardFactory;
    }

    /**
     * Check if order is paid exactly now
     *
     * If order was paid before Rewards were enabled, reward points should not be added
     *
     * @param Order $order
     * @return bool
     */
    protected function _isOrderPaidNow($order)
    {
        $baseGrandTotal = (float) $order->getBaseGrandTotal();
        $baseSubtotalCanceled = (float) $order->getBaseSubtotalCanceled();
        $baseTaxCanceled = (float) $order->getBaseTaxCanceled();
        $baseTotalPaid = (float) $order->getBaseTotalPaid();
        $totalAmountPaid = $baseGrandTotal - ($baseSubtotalCanceled + $baseTaxCanceled + $baseTotalPaid);
        $isOrderPaid = (double) $order->getBaseTotalPaid() > 0 &&
            $totalAmountPaid < 0.0001;

        if (!$order->getOrigData('base_grand_total')) {
            //New order with "Sale" payment action
            return $isOrderPaid;
        }

        return $isOrderPaid && $order->getOrigData(
            'base_grand_total'
        ) - $order->getOrigData(
            'base_subtotal_canceled'
        ) - $order->getOrigData(
            'base_tax_canceled'
        ) - $order->getOrigData(
            'base_total_paid'
        ) >= 0.0001;
    }

    /**
     * Update points balance after order becomes completed
     *
     * @param Observer $observer
     * @return $this
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        /* @var $order Order */
        $order = $observer->getEvent()->getOrder();
        if ($order->getCustomerIsGuest() || !$this->_rewardData->isEnabledOnFront($order->getStore()->getWebsiteId())) {
            return $this;
        }

        if ($order->getCustomerId() && $this->_isOrderPaidNow($order)) {
            /* @var $reward Reward */
            $reward = $this->_rewardFactory->create()->setActionEntity(
                $order
            )->setCustomerId(
                $order->getCustomerId()
            )->setWebsiteId(
                $order->getStore()->getWebsiteId()
            )->setAction(
                Reward::REWARD_ACTION_ORDER_EXTRA
            )->updateRewardPoints();
            if ($reward->getRewardPointsUpdated() && $reward->getPointsDelta()) {
                $order->addStatusHistoryComment(
                    __(
                        'The customer earned %1 for this order.',
                        $this->_rewardData->formatReward($reward->getPointsDelta())
                    )
                )->save();
            }
        }

        return $this;
    }
}
