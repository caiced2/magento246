<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProcessBeforeOrderPlaceObserver implements ObserverInterface
{
    /**
     * Customer balance data
     *
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceData;

    /**
     * @var CheckStoreCreditBalance
     */
    protected $checkStoreCreditBalance;

    /**
     * Constructor
     *
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceData
     * @param CheckStoreCreditBalance $checkStoreCreditBalance
     */
    public function __construct(
        \Magento\CustomerBalance\Helper\Data $customerBalanceData,
        CheckStoreCreditBalance $checkStoreCreditBalance
    ) {
        $this->_customerBalanceData = $customerBalanceData;
        $this->checkStoreCreditBalance = $checkStoreCreditBalance;
    }

    /**
     * Validate balance just before placing an order
     *
     * @param   \Magento\Framework\Event\Observer $observer
     * @return  $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_customerBalanceData->isEnabled()) {
            $order = $observer->getEvent()->getOrder();
            $this->checkStoreCreditBalance->execute($order);
        }
        return $this;
    }
}
