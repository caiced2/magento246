<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class ApplyBlockPersistentDataObserver implements ObserverInterface
{
    /**
     * @var \Magento\Persistent\Observer\ApplyBlockPersistentDataObserver
     */
    protected $_observer;

    /**
     * Persistent data
     *
     * @var \Magento\PersistentHistory\Helper\Data
     */
    protected $_ePersistentData = null;

    /**
     * @param \Magento\PersistentHistory\Helper\Data $ePersistentData
     * @param \Magento\Persistent\Observer\ApplyBlockPersistentDataObserver $observer
     */
    public function __construct(
        \Magento\PersistentHistory\Helper\Data $ePersistentData,
        \Magento\Persistent\Observer\ApplyBlockPersistentDataObserver $observer
    ) {
        $this->_ePersistentData = $ePersistentData;
        $this->_observer = $observer;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $observer->getEvent()->setConfigFilePath($this->_ePersistentData->getPersistentConfigFilePath());
        return $this->_observer->execute($observer);
    }
}
