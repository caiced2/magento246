<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Observer;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\CustomerSegment\Model\Customer as CustomerSegmentCustomer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Data\CustomerSecureFactory;

/**
 * Customer segment generic events processor
 */
class ProcessEventGenericObserver implements ObserverInterface
{
    /**
     * Default name of event data object
     */
    private const DEFAULT_EVENT_DATA_OBJECT_NAME = 'data_object';

    /**
     * @var CustomerSegmentCustomer
     */
    private $customerSegmentCustomer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var array
     */
    private $eventDataObjectNames;

    /**
     * @param StoreManagerInterface $storeManager
     * @param UserContextInterface $userContext
     * @param CustomerSegmentCustomer $customerSegmentCustomer
     * @param CustomerRegistry $customerRegistry
     * @param CustomerFactory $customerFactory
     * @param array $eventDataObjectNames
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        UserContextInterface $userContext,
        CustomerSegmentCustomer $customerSegmentCustomer,
        CustomerRegistry $customerRegistry,
        CustomerFactory $customerFactory,
        array $eventDataObjectNames = []
    ) {
        $this->storeManager = $storeManager;
        $this->customerSegmentCustomer = $customerSegmentCustomer;
        $this->customerRegistry = $customerRegistry;
        $this->userContext = $userContext;
        $this->customerFactory = $customerFactory;
        $this->eventDataObjectNames = $eventDataObjectNames;
    }

    /**
     * Match customer segments on supplied event for currently logged in customer or visitor and current website.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $customer = null;
        $customerId = null;
        $eventName = $observer->getEvent()->getName();
        $dataObjectName = $this->eventDataObjectNames[$eventName] ?? self::DEFAULT_EVENT_DATA_OBJECT_NAME;
        $dataObject = $observer->getData($dataObjectName);
        if ($dataObject !== null) {
            if ($dataObject instanceof Customer) {
                $customer = $dataObject;
            } elseif ($dataObject instanceof CustomerInterface) {
                $customerId = (int) $dataObject->getId();
            } elseif ($dataObject instanceof AbstractModel) {
                $customerId = (int) $dataObject->getCustomerId();
            }
        }
        if ($customerId) {
            $customer = $this->customerRegistry->retrieve($customerId);
        }
        if ($customer === null) {
            $customer = $this->getAuthenticatedCustomer();
        }

        $this->customerSegmentCustomer->processEvent(
            $eventName,
            $customer,
            $this->storeManager->getStore()->getWebsite()
        );
    }

    /**
     * Get current authenticated customer model
     *
     * @return Customer
     * @throws NoSuchEntityException
     */
    private function getAuthenticatedCustomer(): Customer
    {
        if ($this->userContext->getUserId()
            && $this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER
        ) {
            $customer = $this->customerRegistry->retrieve($this->userContext->getUserId());
        } else {
            $customer = $this->customerFactory->create();
        }
        return $customer;
    }
}
