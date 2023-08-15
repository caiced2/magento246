<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer;

use Magento\Framework\Event\ObserverInterface;

class RevertRewardPointsForAllOrders implements ObserverInterface
{
    /**
     * @var \Magento\Reward\Model\Reward\Reverter
     */
    protected $rewardReverter;

    /**
     * @param \Magento\Reward\Model\Reward\Reverter $reverter
     */
    public function __construct(\Magento\Reward\Model\Reward\Reverter $reverter)
    {
        $this->rewardReverter = $reverter;
    }

    /**
     * Revert authorized reward points amounts for all orders
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orders = $observer->getEvent()->getOrders();

        foreach ($orders as $order) {
            $this->rewardReverter->revertRewardPointsForOrder($order);
        }

        return $this;
    }
}
