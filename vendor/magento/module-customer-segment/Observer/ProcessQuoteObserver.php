<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProcessQuoteObserver implements ObserverInterface
{
    /**
     * @var \Magento\CustomerSegment\Model\Customer
     */
    protected $_customer;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @param \Magento\CustomerSegment\Model\Customer $customer
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        \Magento\CustomerSegment\Model\Customer $customer,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->_customer = $customer;
        $this->customerFactory = $customerFactory;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * Match quote customer to all customer segments.
     * Used before quote recollect in admin
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $quote \Magento\Quote\Model\Quote
         */
        $quote = $observer->getEvent()->getQuote();
        $customerData = $quote->getCustomer();
        if ($customerData && $customerData->getId()) {
            $customer = $this->customerFactory->create(
                [
                    'data' => $this->extensibleDataObjectConverter->toFlatArray($customerData),
                ]
            );
            $customer->setId($customerData->getId());
            $website = $quote->getStore()->getWebsite();
            $this->_customer->processCustomer($customer, $website);
        }
    }
}
