<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Total\Invoice;

class Giftcardaccount extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * Collect gift card account totals for invoice
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();
        if ($order->getBaseGiftCardsAmount() && $order->getBaseGiftCardsInvoiced() != $order->getBaseGiftCardsAmount()
        ) {
            $gcaLeft = $order->getBaseGiftCardsAmount() - $order->getBaseGiftCardsInvoiced();
            $used = 0;
            $baseUsed = 0;
            if ($gcaLeft >= $invoice->getBaseGrandTotal()) {
                $baseUsed = $invoice->getBaseGrandTotal();
                $used = $invoice->getGrandTotal();

                $invoice->setBaseGrandTotal(0);
                $invoice->setGrandTotal(0);
            } else {
                $baseUsed = $order->getBaseGiftCardsAmount() - $order->getBaseGiftCardsInvoiced();
                $used = $order->getGiftCardsAmount() - $order->getGiftCardsInvoiced();

                $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $baseUsed);
                $invoice->setGrandTotal($invoice->getGrandTotal() - $used);
            }

            $invoice->setBaseGiftCardsAmount($baseUsed);
            $invoice->setGiftCardsAmount($used);
        }
        return $this;
    }
}
