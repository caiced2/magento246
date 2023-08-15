<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class for process order through customer balance.
 */
class ProcessOrderCreationDataObserver implements ObserverInterface
{
    /**
     * Customer balance data
     *
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceData;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * Constructor
     *
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceData
     */
    public function __construct(
        \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CustomerBalance\Helper\Data $customerBalanceData
    ) {
        $this->_balanceFactory = $balanceFactory;
        $this->_storeManager = $storeManager;
        $this->_customerBalanceData = $customerBalanceData;
    }

    /**
     * The same as paymentDataImport(), but for admin checkout
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_customerBalanceData->isEnabled()) {
            return $this;
        }
        $quote = $observer->getEvent()->getOrderCreateModel()->getQuote();
        $request = $observer->getEvent()->getRequest();
        if (isset($request['payment']) && isset($request['payment']['use_customer_balance'])) {
            $this->_importPaymentData(
                $quote,
                $quote->getPayment(),
                (bool)(int)$request['payment']['use_customer_balance']
            );
        }

        return $this;
    }

    /**
     * Analyze payment data for quote and set free shipping if grand total is covered by balance
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Framework\DataObject|\Magento\Quote\Model\Quote\Payment $payment
     * @param bool $shouldUseBalance
     * @return void
     */
    protected function _importPaymentData($quote, $payment, $shouldUseBalance)
    {
        $store = $this->_storeManager->getStore($quote->getStoreId());
        if (!$quote ||
            !$quote->getCustomerId() ||
            $quote->getBaseGrandTotal() + $quote->getBaseCustomerBalAmountUsed() <= 0
        ) {
            return;
        }
        $quote->setUseCustomerBalance($shouldUseBalance);
        if ($shouldUseBalance) {
            $balance = $this->_balanceFactory->create()->setCustomerId(
                $quote->getCustomerId()
            )->setWebsiteId(
                $store->getWebsiteId()
            )->loadByCustomer();
            if ($balance) {
                $quote->setCustomerBalanceInstance($balance);
                if (!$payment->getMethod()) {
                    $payment->setMethod('free');
                }
            } else {
                $quote->setUseCustomerBalance(false);
            }
        }
    }
}
