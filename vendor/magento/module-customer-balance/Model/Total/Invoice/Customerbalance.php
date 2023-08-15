<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Total\Invoice;

class Customerbalance extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * Customer balance data
     *
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceData = null;

    /**
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceData
     * @param array $data
     */
    public function __construct(\Magento\CustomerBalance\Helper\Data $customerBalanceData, array $data = [])
    {
        $this->_customerBalanceData = $customerBalanceData;
        parent::__construct($data);
    }

    /**
     * Collect customer balance totals for invoice
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        if (!$this->_customerBalanceData->isEnabled()) {
            return $this;
        }
        $order = $invoice->getOrder();
        if ($order->getBaseCustomerBalanceAmount() &&
            $order->getBaseCustomerBalanceInvoiced() != $order->getBaseCustomerBalanceAmount()
        ) {
            $gcaLeft = $order->getBaseCustomerBalanceAmount() - $order->getBaseCustomerBalanceInvoiced();
            $used = 0;
            $baseUsed = 0;
            if ($gcaLeft >= $invoice->getBaseGrandTotal()) {
                $baseUsed = $invoice->getBaseGrandTotal();
                $used = $invoice->getGrandTotal();

                $invoice->setBaseGrandTotal(0);
                $invoice->setGrandTotal(0);
            } else {
                $baseUsed = $order->getBaseCustomerBalanceAmount() - $order->getBaseCustomerBalanceInvoiced();
                $used = $order->getCustomerBalanceAmount() - $order->getCustomerBalanceInvoiced();

                $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $baseUsed);
                $invoice->setGrandTotal($invoice->getGrandTotal() - $used);
            }

            $invoice->setBaseCustomerBalanceAmount($baseUsed);
            $invoice->setCustomerBalanceAmount($used);
        }
        return $this;
    }
}
