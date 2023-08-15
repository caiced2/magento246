<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Revert reward points if order was not placed.
 */
class RevertRewardPoints implements ObserverInterface
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
     * Revert reward points if order was not placed
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getOrder();
        if ($order) {
            $this->rewardReverter->revertRewardPointsForOrder($order);
        }

        return $this;
    }
}
