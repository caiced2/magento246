<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * GiftWrapping total tax calculator for invoice
 *
 */
namespace Magento\GiftWrapping\Model\Total\Invoice\Tax;

class Giftwrapping extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * Collect gift wrapping tax totals
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return \Magento\GiftWrapping\Model\Total\Invoice\Tax\Giftwrapping
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
                $orderItem->getGwBaseTaxAmount() &&
                $orderItem->getGwBaseTaxAmount() != $orderItem->getGwBaseTaxAmountInvoiced()
            ) {
                $orderItem->setGwBaseTaxAmountInvoiced($orderItem->getGwBaseTaxAmount());
                $orderItem->setGwTaxAmountInvoiced($orderItem->getGwTaxAmount());
                $baseInvoiced += $orderItem->getGwBaseTaxAmount() * $invoiceItem->getQty();
                $invoiced += $orderItem->getGwTaxAmount() * $invoiceItem->getQty();
            }
        }
        if ($invoiced > 0 || $baseInvoiced > 0) {
            $order->setGwItemsBaseTaxInvoiced($order->getGwItemsBaseTaxInvoiced() + $baseInvoiced);
            $order->setGwItemsTaxInvoiced($order->getGwItemsTaxInvoiced() + $invoiced);
            $invoice->setGwItemsBaseTaxAmount($baseInvoiced);
            $invoice->setGwItemsTaxAmount($invoiced);
        }

        /**
         * Wrapping for order
         */
        if ($order->getGwId() &&
            $order->getGwBaseTaxAmount() &&
            $order->getGwBaseTaxAmount() != $order->getGwBaseTaxAmountInvoiced()
        ) {
            $order->setGwBaseTaxAmountInvoiced($order->getGwBaseTaxAmount());
            $order->setGwTaxAmountInvoiced($order->getGwTaxAmount());
            $invoice->setGwBaseTaxAmount($order->getGwBaseTaxAmount());
            $invoice->setGwTaxAmount($order->getGwTaxAmount());
        }

        /**
         * Printed card
         */
        if ($order->getGwAddCard() &&
            $order->getGwCardBaseTaxAmount() &&
            $order->getGwCardBaseTaxAmount() != $order->getGwCardBaseTaxInvoiced()
        ) {
            $order->setGwCardBaseTaxInvoiced($order->getGwCardBaseTaxAmount());
            $order->setGwCardTaxInvoiced($order->getGwCardTaxAmount());
            $invoice->setGwCardBaseTaxAmount($order->getGwCardBaseTaxAmount());
            $invoice->setGwCardTaxAmount($order->getGwCardTaxAmount());
        }

        if (!$invoice->isLast()) {
            $baseTaxAmount = $invoice->getGwItemsBaseTaxAmount() +
                $invoice->getGwBaseTaxAmount() +
                $invoice->getGwCardBaseTaxAmount();
            $taxAmount = $invoice->getGwItemsTaxAmount() + $invoice->getGwTaxAmount() + $invoice->getGwCardTaxAmount();
            $invoice->setBaseTaxAmount($invoice->getBaseTaxAmount() + $baseTaxAmount);
            $invoice->setTaxAmount($invoice->getTaxAmount() + $taxAmount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseTaxAmount);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $taxAmount);
        }

        return $this;
    }
}
