<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class UpdateOptionCustomerSegmentationObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_valueFactory;

    /**
     * @param \Magento\Framework\App\Config\ValueFactory $valueFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ValueFactory $valueFactory
    ) {
        $this->_valueFactory = $valueFactory;
    }

    /**
     * Update Option "Persist Customer Group Membership and Segmentation"
     * set value "Yes" if option "Persist Shopping Cart" equals "Yes"
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $eventDataObject = $observer->getEvent()->getDataObject();

        if ($eventDataObject->getValue()) {
            $this->_valueFactory->create()->setScope(
                $eventDataObject->getScope()
            )->setScopeId(
                $eventDataObject->getScopeId()
            )->setPath(
                \Magento\PersistentHistory\Helper\Data::XML_PATH_PERSIST_CUSTOMER_AND_SEGM
            )->setValue(
                true
            )->save();
        }
    }
}
