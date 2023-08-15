<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * GiftWrapping total calculator for invoice
 *
 */
namespace Magento\GiftWrapping\Model\Total\Invoice;

class Giftwrapping extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * Collect gift wrapping totals
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return \Magento\GiftWrapping\Model\Total\Invoice\Giftwrapping
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();

        /**
         * Wrapping for items
         */
        $invoiced = 0;
        $baseInvoiced = 0;
        foreach ($invoice->getAllItems() as $invoiceItem) {
            if (!$invoiceItem->getQty() || $invoiceItem->getQty() == 0) {
                continue;
            }
            $orderItem = $invoiceItem->getOrderItem();
            if ($orderItem->getGwId() &&
                $orderItem->getGwBasePrice() &&
                $orderItem->getGwBasePrice() != $orderItem->getGwBasePriceInvoiced()
            ) {
                $orderItem->setGwBasePriceInvoiced($orderItem->getGwBasePrice());
                $orderItem->setGwPriceInvoiced($orderItem->getGwPrice());
                $baseInvoiced += $orderItem->getGwBasePrice() * $invoiceItem->getQty();
                $invoiced += $orderItem->getGwPrice() * $invoiceItem->getQty();
            }
        }
        if ($invoiced > 0 || $baseInvoiced > 0) {
            $order->setGwItemsBasePriceInvoiced($order->getGwItemsBasePriceInvoiced() + $baseInvoiced);
            $order->setGwItemsPriceInvoiced($order->getGwItemsPriceInvoiced() + $invoiced);
            $invoice->setGwItemsBasePrice($baseInvoiced);
            $invoice->setGwItemsPrice($invoiced);
        }

        /**
         * Wrapping for order
         */
        if ($order->getGwId() &&
            $order->getGwBasePrice() &&
            $order->getGwBasePrice() != $order->getGwBasePriceInvoiced()
        ) {
            $order->setGwBasePriceInvoiced($order->getGwBasePrice());
            $order->setGwPriceInvoiced($order->getGwPrice());
            $invoice->setGwBasePrice($order->getGwBasePrice());
            $invoice->setGwPrice($order->getGwPrice());
        }

        /**
         * Printed card
         */
        if ($order->getGwAddCard() &&
            $order->getGwCardBasePrice() &&
            $order->getGwCardBasePrice() != $order->getGwCardBasePriceInvoiced()
        ) {
            $order->setGwCardBasePriceInvoiced($order->getGwCardBasePrice());
            $order->setGwCardPriceInvoiced($order->getGwCardPrice());
            $invoice->setGwCardBasePrice($order->getGwCardBasePrice());
            $invoice->setGwCardPrice($order->getGwCardPrice());
        }

        $invoice->setBaseGrandTotal(
            $invoice->getBaseGrandTotal() +
            $invoice->getGwItemsBasePrice() +
            $invoice->getGwBasePrice() +
            $invoice->getGwCardBasePrice()
        );
        $invoice->setGrandTotal(
            $invoice->getGrandTotal() +
            $invoice->getGwItemsPrice() +
            $invoice->getGwPrice() +
            $invoice->getGwCardPrice()
        );
        return $this;
    }
}
