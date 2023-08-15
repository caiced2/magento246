<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\Total\Creditmemo\Tax;

/**
 * GiftWrapping total tax calculator for creditmemo
 */
class Giftwrapping extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    /**
     * Collect gift wrapping tax totals
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return \Magento\GiftWrapping\Model\Total\Creditmemo\Tax\Giftwrapping
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
                $orderItem->getGwBaseTaxAmountInvoiced() &&
                $orderItem->getGwBaseTaxAmountInvoiced() != $orderItem->getGwBaseTaxAmountRefunded()
            ) {
                $orderItem->setGwBaseTaxAmountRefunded($orderItem->getGwBaseTaxAmountInvoiced());
                $orderItem->setGwTaxAmountRefunded($orderItem->getGwTaxAmountInvoiced());
                $baseRefunded += $orderItem->getGwBaseTaxAmountInvoiced() * $creditmemoItem->getQty();
                $refunded += $orderItem->getGwTaxAmountInvoiced() * $creditmemoItem->getQty();
            }
        }
        if ($refunded > 0 || $baseRefunded > 0) {
            $order->setGwItemsBaseTaxRefunded($order->getGwItemsBaseTaxRefunded() + $baseRefunded);
            $order->setGwItemsTaxRefunded($order->getGwItemsTaxRefunded() + $refunded);
            $creditmemo->setGwItemsBaseTaxAmount($baseRefunded);
            $creditmemo->setGwItemsTaxAmount($refunded);
        }

        /**
         * Wrapping for order
         */
        if ($order->getGwId() &&
            $order->getGwBaseTaxAmountInvoiced() &&
            $order->getGwBaseTaxAmountInvoiced() != $order->getGwBaseTaxAmountRefunded()
        ) {
            $order->setGwBaseTaxAmountRefunded($order->getGwBaseTaxAmountInvoiced());
            $order->setGwTaxAmountRefunded($order->getGwTaxAmountInvoiced());
            $creditmemo->setGwBaseTaxAmount($order->getGwBaseTaxAmountInvoiced());
            $creditmemo->setGwTaxAmount($order->getGwTaxAmountInvoiced());
        }

        /**
         * Printed card
         */
        if ($order->getGwAddCard() &&
            $order->getGwCardBaseTaxInvoiced() &&
            $order->getGwCardBaseTaxInvoiced() != $order->getGwCardBaseTaxRefunded()
        ) {
            $order->setGwCardBaseTaxRefunded($order->getGwCardBaseTaxInvoiced());
            $order->setGwCardTaxRefunded($order->getGwCardTaxInvoiced());
            $creditmemo->setGwCardBaseTaxAmount($order->getGwCardBaseTaxInvoiced());
            $creditmemo->setGwCardTaxAmount($order->getGwCardTaxInvoiced());
        }
        if (!$creditmemo->isLast()) {
            $baseTaxAmount = $creditmemo->getGwItemsBaseTaxAmount() +
                $creditmemo->getGwBaseTaxAmount() +
                $creditmemo->getGwCardBaseTaxAmount();
            $taxAmount = $creditmemo->getGwItemsTaxAmount() +
                $creditmemo->getGwTaxAmount() +
                $creditmemo->getGwCardTaxAmount();
            $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $baseTaxAmount);
            $creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $taxAmount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseTaxAmount);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $taxAmount);
        }
        return $this;
    }
}
