<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Customer balance observer
 */
class CreditmemoSaveAfterObserver implements ObserverInterface
{
    /**
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceData;

    /**
     * @var \Magento\CustomerBalance\Model\Creditmemo\Balance
     */
    private $balance;

    /**
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceData
     * @param \Magento\CustomerBalance\Model\Creditmemo\Balance $balance
     */
    public function __construct(
        \Magento\CustomerBalance\Helper\Data $customerBalanceData,
        \Magento\CustomerBalance\Model\Creditmemo\Balance $balance
    ) {
        $this->_customerBalanceData = $customerBalanceData;
        $this->balance = $balance;
    }

    /**
     * Refund process.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();

        if ($creditmemo->getAutomaticallyCreated()) {
            if ($this->_customerBalanceData->isAutoRefundEnabled()) {
                $creditmemo->setCustomerBalanceRefundFlag(true)
                    ->setCustomerBalTotalRefunded($creditmemo->getCustomerBalanceAmount())
                    ->setBsCustomerBalTotalRefunded($creditmemo->getBaseCustomerBalanceAmount())
                    ->setCustomerBalanceRefunded($creditmemo->getCustomerBalanceAmount())
                    ->setBaseCustomerBalanceRefunded($creditmemo->getBaseCustomerBalanceAmount());
            } else {
                return $this;
            }
        }

        if ($creditmemo->getCustomerBalanceRefundFlag() && $creditmemo->getBsCustomerBalTotalRefunded()) {
            $this->balance->save($creditmemo);
        }

        return $this;
    }
}
