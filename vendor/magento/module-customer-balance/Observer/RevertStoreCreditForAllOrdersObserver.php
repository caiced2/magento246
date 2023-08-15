<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;

class RevertStoreCreditForAllOrdersObserver implements ObserverInterface
{
    /**
     * @var RevertStoreCreditForOrder
     */
    protected $revertStoreCreditForOrder;

    /**
     * Constructor
     *
     * @param RevertStoreCreditForOrder $revertStoreCreditForOrder
     */
    public function __construct(
        RevertStoreCreditForOrder $revertStoreCreditForOrder
    ) {
        $this->revertStoreCreditForOrder = $revertStoreCreditForOrder;
    }

    /**
     * Revert authorized store credit amounts for all orders
     *
     * @param   \Magento\Framework\Event\Observer $observer
     * @return  $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orders = $observer->getEvent()->getOrders();
        foreach ($orders as $order) {
            $this->revertStoreCreditForOrder->execute($order);
        }
        return $this;
    }
}
