<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class ApplyPersistentDataObserver implements ObserverInterface
{
    /**
     * @var \Magento\Persistent\Model\Persistent\ConfigFactory
     */
    protected $_configFactory;

    /**
     * Persistent session
     *
     * @var \Magento\Persistent\Helper\Session
     */
    protected $_persistentSession = null;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Persistent data
     *
     * @var \Magento\PersistentHistory\Helper\Data
     */
    protected $_ePersistentData = null;

    /**
     * Persistent data
     *
     * @var \Magento\Persistent\Helper\Data
     */
    protected $_mPersistentData = null;

    /**
     * @param \Magento\Persistent\Helper\Session $persistentSession
     * @param \Magento\PersistentHistory\Helper\Data $ePersistentData
     * @param \Magento\Persistent\Helper\Data $mPersistentData
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Persistent\Model\Persistent\ConfigFactory $configFactory
     */
    public function __construct(
        \Magento\Persistent\Helper\Session $persistentSession,
        \Magento\PersistentHistory\Helper\Data $ePersistentData,
        \Magento\Persistent\Helper\Data $mPersistentData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Persistent\Model\Persistent\ConfigFactory $configFactory
    ) {
        $this->_persistentSession = $persistentSession;
        $this->_mPersistentData = $mPersistentData;
        $this->_ePersistentData = $ePersistentData;
        $this->_customerSession = $customerSession;
        $this->_configFactory = $configFactory;
    }

    /**
     * Apply persistent data
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_mPersistentData->canProcess($observer)
            || !$this->_persistentSession->isPersistent()
            || $this->_customerSession->isLoggedIn()
        ) {
            return;
        }
        $this->_configFactory->create()->setConfigFilePath(
            $this->_ePersistentData->getPersistentConfigFilePath()
        )->fire();
    }
}
