<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Observer for converting quote customer custom attributes to order customer custom attributes
 */
class CoreCopyDataQuoteToOrder extends AbstractObserver implements ObserverInterface
{
    /**
     * Observer for converting quote data to order
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        $attributes = $this->_customerData->getCustomerUserDefinedAttributeCodes();
        $prefix = 'customer_';

        foreach ($attributes as $attribute) {
            $sourceAttribute = $prefix . $attribute;
            $targetAttribute = $prefix . $attribute;
            $order->setData($targetAttribute, $quote->getData($sourceAttribute));
        }
        return $this;
    }
}
