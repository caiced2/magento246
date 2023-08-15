<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Add gift wrapping items into payment checkout
 */
class AddPaymentGiftWrappingItem implements ObserverInterface
{
    /**
     * Add gift wrapping items into payment checkout
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Payment\Model\Cart $cart */
        $cart = $observer->getEvent()->getCart();
        $totalWrapping = $this->getGiftWrappingTotalAmount($cart);
        $totalCard = $this->getGiftWrappingCardTotalAmount($cart);

        if ($totalWrapping) {
            $cart->addCustomItem(__('Gift Wrapping'), 1, $totalWrapping);
        }
        if ($totalCard) {
            $cart->addCustomItem(__('Printed Card'), 1, $totalCard);
        }
    }

    /**
     * Returns the total amount of gift wrapping in the cart
     *
     * @param \Magento\Payment\Model\Cart $cart
     * @return float
     */
    private function getGiftWrappingTotalAmount(\Magento\Payment\Model\Cart $cart): float
    {
        $totalWrapping = 0;
        $salesEntity = $cart->getSalesModel();

        foreach ($salesEntity->getAllItems() as $item) {
            $originalItem = $item->getOriginalItem();
            if (!$originalItem->getParentItem() && $originalItem->getGwId() && $originalItem->getGwBasePrice()) {
                $qty = $originalItem instanceof \Magento\Sales\Api\Data\OrderItemInterface
                    ? $originalItem->getQtyOrdered()
                    : $originalItem->getQty();
                $totalWrapping += $originalItem->getGwBasePrice() * $qty;
            }
        }

        if ($salesEntity->getDataUsingMethod('gw_id') && $salesEntity->getDataUsingMethod('gw_base_price')) {
            $totalWrapping += $salesEntity->getDataUsingMethod('gw_base_price');
        }

        return $totalWrapping;
    }

    /**
     * Returns the total amount of gift wrapping card in the cart
     *
     * @param \Magento\Payment\Model\Cart $cart
     * @return float
     */
    private function getGiftWrappingCardTotalAmount(\Magento\Payment\Model\Cart $cart): float
    {
        $totalCard = 0;
        $salesEntity = $cart->getSalesModel();

        if ($salesEntity->getDataUsingMethod('gw_add_card') && $salesEntity->getDataUsingMethod('gw_card_base_price')) {
            $totalCard += $salesEntity->getDataUsingMethod('gw_card_base_price');
        }

        return $totalCard;
    }
}
