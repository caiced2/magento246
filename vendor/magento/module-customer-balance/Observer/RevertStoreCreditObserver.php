<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;

class RevertStoreCreditObserver implements ObserverInterface
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
     * Revert store credit if order was not placed
     *
     * @param   \Magento\Framework\Event\Observer $observer
     * @return  $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getOrder();
        if ($order) {
            $this->revertStoreCreditForOrder->execute($order);
        }

        return $this;
    }
}
