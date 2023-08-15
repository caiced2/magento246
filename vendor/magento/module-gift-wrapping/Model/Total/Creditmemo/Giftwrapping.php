<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * GiftWrapping total calculator for creditmemo
 *
 */
namespace Magento\GiftWrapping\Model\Total\Creditmemo;

class Giftwrapping extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    /**
     * Collect gift wrapping totals
     *
     * @param   \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return  \Magento\GiftWrapping\Model\Total\Creditmemo\Giftwrapping
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        /**
         * Wrapping for items
         */
        $refunded = 0;
        $baseRefunded = 0;
        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            if (!$creditmemoItem->getQty() || $creditmemoItem->getQty() == 0) {
                continue;
            }
            $orderItem = $creditmemoItem->getOrderItem();
            if ($orderItem->getGwId() &&
                $orderItem->getGwBasePriceInvoiced() &&
                $orderItem->getGwBasePriceInvoiced() != $orderItem->getGwBasePriceRefunded()
            ) {
                $orderItem->setGwBasePriceRefunded($orderItem->getGwBasePriceInvoiced());
                $orderItem->setGwPriceRefunded($orderItem->getGwPriceInvoiced());
                $baseRefunded += $orderItem->getGwBasePriceInvoiced() * $creditmemoItem->getQty();
                $refunded += $orderItem->getGwPriceInvoiced() * $creditmemoItem->getQty();
            }
        }
        if ($refunded > 0 || $baseRefunded > 0) {
            $order->setGwItemsBasePriceRefunded($order->getGwItemsBasePriceRefunded() + $baseRefunded);
            $order->setGwItemsPriceRefunded($order->getGwItemsPriceRefunded() + $refunded);
            $creditmemo->setGwItemsBasePrice($baseRefunded);
            $creditmemo->setGwItemsPrice($refunded);
        }

        /**
         * Wrapping for order
         */
        if ($order->getGwId() &&
            $order->getGwBasePriceInvoiced() &&
            $order->getGwBasePriceInvoiced() != $order->getGwBasePriceRefunded()
        ) {
            $order->setGwBasePriceRefunded($order->getGwBasePriceInvoiced());
            $order->setGwPriceRefunded($order->getGwPriceInvoiced());
            $creditmemo->setGwBasePrice($order->getGwBasePriceInvoiced());
            $creditmemo->setGwPrice($order->getGwPriceInvoiced());
        }

        /**
         * Printed card
         */
        if ($order->getGwAddCard() &&
            $order->getGwCardBasePriceInvoiced() &&
            $order->getGwCardBasePriceInvoiced() != $order->getGwCardBasePriceRefunded()
        ) {
            $order->setGwCardBasePriceRefunded($order->getGwCardBasePriceInvoiced());
            $order->setGwCardPriceRefunded($order->getGwCardPriceInvoiced());
            $creditmemo->setGwCardBasePrice($order->getGwCardBasePriceInvoiced());
            $creditmemo->setGwCardPrice($order->getGwCardPriceInvoiced());
        }

        $creditmemo->setBaseGrandTotal(
            $creditmemo->getBaseGrandTotal() +
            $creditmemo->getGwItemsBasePrice() +
            $creditmemo->getGwBasePrice() +
            $creditmemo->getGwCardBasePrice()
        );
        $creditmemo->setGrandTotal(
            $creditmemo->getGrandTotal() +
            $creditmemo->getGwItemsPrice() +
            $creditmemo->getGwPrice() +
            $creditmemo->getGwCardPrice()
        );

        $creditmemo->setBaseCustomerBalanceReturnMax(
            $creditmemo->getBaseCustomerBalanceReturnMax() +
            $creditmemo->getGwCardBasePrice() +
            $creditmemo->getGwBasePrice() +
            $creditmemo->getGwItemsBasePrice()
        );
        $creditmemo->setCustomerBalanceReturnMax(
            $creditmemo->getCustomerBalanceReturnMax() +
            $creditmemo->getGwCardPrice() +
            $creditmemo->getGwPrice() +
            $creditmemo->getGwItemsPrice()
        );

        return $this;
    }
}
