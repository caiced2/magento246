<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;

class PaymentDataImportObserver implements ObserverInterface
{
    /**
     * Customer balance data
     *
     * @var \Magento\CustomerBalance\Model\Balance
     */
    protected $customerBalance;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param \Magento\CustomerBalance\Model\Balance $customerBalanceData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagee
     */
    public function __construct(
        \Magento\CustomerBalance\Model\Balance $customerBalanceData,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->customerBalance = $customerBalanceData;
        $this->storeManager = $storeManager;
    }

    /**
     * Defined in Logging/etc/logging.xml - special handler for setting second action for customerBalance change
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var  \Magento\Framework\Event $event */
        $event = $observer->getEvent();
        /** @var  \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $event->getPayment();
        $quote = $payment->getQuote();
        if ($quote->getIsMultiShipping()) {
            $input = $event->getInput();
            $additionalData = (array)$input->getAdditionalData();
            if (isset($additionalData['use_customer_balance'])) {
                $this->_importPaymentData($quote, $input, $additionalData['use_customer_balance']);
            };
        }
    }

    /**
     * Analyze payment data for quote and set free shipping if grand total is covered by balance
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Framework\DataObject $payment
     * @param bool $shouldUseBalance
     * @return void
     */
    protected function _importPaymentData($quote, $payment, $shouldUseBalance)
    {
        $store = $this->storeManager->getStore($quote->getStoreId());
        $customerId = $quote->getCustomerId();
        if (!$quote || !$customerId) {
            return;
        }
        $quote->setUseCustomerBalance($shouldUseBalance);
        if ($shouldUseBalance) {
            $balance = $this->customerBalance
                ->setCustomerId($customerId)
                ->setWebsiteId($store->getWebsiteId())
                ->loadByCustomer();
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
