<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Observer;

use Magento\Framework\Event\ObserverInterface;

class CoreCopyFieldsetCheckoutOnepageQuoteToCustomer extends AbstractObserver implements ObserverInterface
{
    /**
     * Observer for converting quote to customer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_copyFieldset($observer, self::CONVERT_ALGORITM_TARGET_WITHOUT_PREFIX, self::CONVERT_TYPE_CUSTOMER);

        return $this;
    }
}
