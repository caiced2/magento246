<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Total\Creditmemo;

/**
 * Calculates credit memo totals
 */
class Customerbalance extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    /**
     * Initialize customer balance data property
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
     * Collect customer balance totals for credit memo
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setBsCustomerBalTotalRefunded(0);
        $creditmemo->setCustomerBalTotalRefunded(0);

        $creditmemo->setBaseCustomerBalanceReturnMax(0);
        $creditmemo->setCustomerBalanceReturnMax(0);

        if (!$this->_customerBalanceData->isEnabled()) {
            return $this;
        }

        $order = $creditmemo->getOrder();
        if ($order->getBaseCustomerBalanceAmount() && $order->getBaseCustomerBalanceInvoiced() != 0) {
            $baseBalanceLeft = max(
                $order->getBaseCustomerBalanceInvoiced() - $order->getBaseCustomerBalanceRefunded(),
                0
            );
            $balanceLeft = max($order->getCustomerBalanceInvoiced() - $order->getCustomerBalanceRefunded(), 0);

            if ($baseBalanceLeft >= $creditmemo->getBaseGrandTotal()) {
                $baseBalanceLeft = $creditmemo->getBaseGrandTotal() ?: $baseBalanceLeft;
                $balanceLeft = $creditmemo->getGrandTotal() ?: $balanceLeft;

                $creditmemo->setBaseGrandTotal(0);
                $creditmemo->setGrandTotal(0);

                $creditmemo->setAllowZeroGrandTotal(true);
            } else {
                $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() - $baseBalanceLeft);
                $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $balanceLeft);
            }

            $creditmemo->setBaseCustomerBalanceAmount($baseBalanceLeft);
            $creditmemo->setCustomerBalanceAmount($balanceLeft);
        }

        $creditmemo->setBaseCustomerBalanceReturnMax(
            $creditmemo->getBaseCustomerBalanceReturnMax()
            + $creditmemo->getBaseGrandTotal()
            + $creditmemo->getBaseCustomerBalanceAmount()
        );

        $creditmemo->setCustomerBalanceReturnMax(
            $creditmemo->getCustomerBalanceReturnMax()
            + $creditmemo->getGrandTotal()
            + $creditmemo->getCustomerBalanceAmount()
        );

        return $this;
    }
}
