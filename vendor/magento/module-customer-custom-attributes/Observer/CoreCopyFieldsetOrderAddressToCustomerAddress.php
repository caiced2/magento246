<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Api\CustomAttributesDataInterface;

class CoreCopyFieldsetOrderAddressToCustomerAddress extends AbstractObserver implements ObserverInterface
{
    /**
     * Observer for converting order address to customer address
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_copyFieldset(
            $observer,
            self::CONVERT_ALGORITM_SOURCE_WITHOUT_PREFIX,
            self::CONVERT_TYPE_CUSTOMER_ADDRESS
        );
        $this->addCustomAddressAttributes($observer);
        return $this;
    }

    /**
     * Add custom address attributes to customer address
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    private function addCustomAddressAttributes(\Magento\Framework\Event\Observer $observer)
    {
        $orderAddress = $observer->getEvent()->getSource();
        $customerAddressData = $observer->getEvent()->getTarget();
        $attributes = $this->_customerData->getCustomerAddressUserDefinedAttributeCodes();

        if ($orderAddress instanceof DataObject && $customerAddressData instanceof DataObject) {
            $customAttributes = $customerAddressData->getData(CustomAttributesDataInterface::CUSTOM_ATTRIBUTES) ?? [];
            foreach ($attributes as $attribute) {
                $customAttributes[$attribute] = $orderAddress->getData($attribute);
            }

            $customerAddressData->setData(CustomAttributesDataInterface::CUSTOM_ATTRIBUTES, $customAttributes);
        }
    }
}
