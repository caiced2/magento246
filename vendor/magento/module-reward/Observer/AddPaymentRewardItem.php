<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddPaymentRewardItem implements ObserverInterface
{
    /**
     * Add reward amount to payment discount total
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Payment\Model\Cart $cart */
        $cart = $observer->getEvent()->getCart();
        $salesEntity = $cart->getSalesModel();
        $discount = abs((float) $salesEntity->getDataUsingMethod('base_reward_currency_amount'));
        if ($discount > 0.0001) {
            $cart->addDiscount((double)$discount);
        }
    }
}
