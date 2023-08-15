<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Observer;

use Magento\Customer\Model\CustomerRegistry;
use Magento\CustomerSegment\Model\Customer as CustomerSegmentCustomer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Customer segment for admin order create observer
 */
class AdminCreateOrderObserver implements ObserverInterface
{
    private const MATCH_EVENT_NAME = 'sales_order_save_commit_after';
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
     * @param StoreManagerInterface $storeManager
     * @param CustomerSegmentCustomer $customerSegmentCustomer
     * @param CustomerRegistry $customerRegistry
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerSegmentCustomer $customerSegmentCustomer,
        CustomerRegistry $customerRegistry
    ) {
        $this->storeManager = $storeManager;
        $this->customerSegmentCustomer = $customerSegmentCustomer;
        $this->customerRegistry = $customerRegistry;
    }

    /**
     * Match customer segments for supplied order.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        if ($order instanceof Order && $order->getCustomerId()) {
            $customer = $this->customerRegistry->retrieve($order->getCustomerId());
            $website = $this->storeManager->getStore($order->getStoreId())->getWebsite();
            $this->customerSegmentCustomer->processEvent(self::MATCH_EVENT_NAME, $customer, $website);
        }
    }
}
