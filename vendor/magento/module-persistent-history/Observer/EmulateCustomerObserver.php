<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class EmulateCustomerObserver implements ObserverInterface
{
    /**
     * Persistent session
     *
     * @var \Magento\Persistent\Helper\Session
     */
    protected $persistentSession = null;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Persistent data
     *
     * @var \Magento\PersistentHistory\Helper\Data
     */
    protected $ePersistentData = null;

    /**
     * Persistent data
     *
     * @var \Magento\Persistent\Helper\Data
     */
    protected $mPersistentData = null;

    /**
     * @var \Magento\PersistentHistory\Model\CustomerEmulator
     */
    protected $customerEmulator;

    /**
     * @param \Magento\Persistent\Helper\Session $persistentSession
     * @param \Magento\PersistentHistory\Helper\Data $ePersistentData
     * @param \Magento\Persistent\Helper\Data $mPersistentData
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\PersistentHistory\Model\CustomerEmulator $customerEmulator
     */
    public function __construct(
        \Magento\Persistent\Helper\Session $persistentSession,
        \Magento\PersistentHistory\Helper\Data $ePersistentData,
        \Magento\Persistent\Helper\Data $mPersistentData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\PersistentHistory\Model\CustomerEmulator $customerEmulator
    ) {
        $this->persistentSession = $persistentSession;
        $this->mPersistentData = $mPersistentData;
        $this->ePersistentData = $ePersistentData;
        $this->customerSession = $customerSession;
        $this->customerEmulator = $customerEmulator;
    }

    /**
     * Set persistent data to customer session
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if ($this->mPersistentData->canProcess($observer)
            && $this->ePersistentData->isCustomerAndSegmentsPersist()
            && $this->persistentSession->isPersistent()
            && !$this->customerSession->isLoggedIn()
        ) {
            $this->customerEmulator->emulate();
        }
    }
}
