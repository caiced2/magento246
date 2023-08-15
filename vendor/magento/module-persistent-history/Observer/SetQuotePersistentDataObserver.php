<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class SetQuotePersistentDataObserver implements ObserverInterface
{
    /**
     * Persistent session
     *
     * @var \Magento\Persistent\Helper\Session
     */
    private $persistentSession = null;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * Persistent data
     *
     * @var \Magento\PersistentHistory\Helper\Data
     */
    private $persistentHistoryDataHelper = null;

    /**
     * Persistent data
     *
     * @var \Magento\Persistent\Helper\Data
     */
    private $persistentDataHelper = null;

    /**
     * Whether set quote to be persistent in workflow
     *
     * @var QuotePersistentPreventFlag
     */
    private $quotePersistent;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Persistent\Helper\Session $persistentSession
     * @param \Magento\PersistentHistory\Helper\Data $persistentHistoryDataHelper
     * @param \Magento\Persistent\Helper\Data $persistentDataHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param QuotePersistentPreventFlag $quotePersistent
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Persistent\Helper\Session $persistentSession,
        \Magento\PersistentHistory\Helper\Data $persistentHistoryDataHelper,
        \Magento\Persistent\Helper\Data $persistentDataHelper,
        \Magento\Customer\Model\Session $customerSession,
        QuotePersistentPreventFlag $quotePersistent,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->persistentSession = $persistentSession;
        $this->persistentDataHelper = $persistentDataHelper;
        $this->persistentHistoryDataHelper = $persistentHistoryDataHelper;
        $this->customerSession = $customerSession;
        $this->quotePersistent = $quotePersistent;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * Set persistent data into quote
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->persistentDataHelper->canProcess($observer) || !$this->persistentSession->isPersistent()) {
            return;
        }

        /** @var $quote \Magento\Quote\Api\Data\CartInterface */
        $quote = $observer->getEvent()->getQuote();
        if (!$quote) {
            return;
        }

        /** @var $customerSession \Magento\Customer\Model\Session */
        $customerSession = $this->customerSession;

        $helper = $this->persistentHistoryDataHelper;
        if ($helper->isCustomerAndSegmentsPersist() && $this->quotePersistent->getValue()) {
            $customerId = $customerSession->getCustomerId();
            if ($customerId) {
                /**
                 * There's extremely rare but theoretically possible situation
                 * when a customer session contains a customer's id, but the customer doesn't exist anymore.
                 * For example, this may happen when the customer has been removed by administrator.
                 * To avoid the whole Magento failure for such case we're catching the NoSuchEntityException.
                 * But anyway, we can't do anything with it here so we should just log it.
                 */
                try {
                    $quote->setCustomer($this->customerRepository->getById($customerId));
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $this->logger->notice(
                        'Cannot restore persistent quote data for customer with id ' . $customerId
                        . ', as those customer doesn\'t exist'
                    );
                }
            }
        }
    }
}
