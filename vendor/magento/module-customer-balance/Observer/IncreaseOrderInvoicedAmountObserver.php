<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;

class IncreaseOrderInvoicedAmountObserver implements ObserverInterface
{
    /**
     * Increase order customer_balance_invoiced attribute based on created invoice
     * used for event: sales_order_invoice_save_after
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        /**
         * Update customer balance only if invoice is just created
         */
        if ($invoice->getOrigData() === null && $invoice->getBaseCustomerBalanceAmount()) {
            $order->setBaseCustomerBalanceInvoiced(
                $order->getBaseCustomerBalanceInvoiced() + $invoice->getBaseCustomerBalanceAmount()
            );
            $order->setCustomerBalanceInvoiced(
                $order->getCustomerBalanceInvoiced() + $invoice->getCustomerBalanceAmount()
            );
        }
        /**
         * Because of order doesn't save second time, added forced saving below attributes
         */
        $order->getResource()->saveAttribute($order, 'base_customer_balance_invoiced');
        $order->getResource()->saveAttribute($order, 'customer_balance_invoiced');
        return $this;
    }
}
