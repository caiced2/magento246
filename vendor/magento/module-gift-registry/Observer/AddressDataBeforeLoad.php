<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\GiftRegistry\Helper\Data;

class AddressDataBeforeLoad implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_giftRegistryData;

    /**
     * @param Data $giftRegistryData
     */
    public function __construct(Data $giftRegistryData)
    {
        $this->_giftRegistryData = $giftRegistryData;
    }

    /**
     * Customer address data object before load processing.
     *
     * Set gift registry item id flag
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $addressId = $observer->getEvent()->getValue();

        if ($addressId !== null && !is_numeric($addressId)) {
            $prefix = $this->_giftRegistryData->getAddressIdPrefix();
            $registryItemId = str_replace($prefix, '', $addressId);
            $object = $observer->getEvent()->getDataObject();
            $object->setGiftregistryItemId($registryItemId);
            $object->setCustomerAddressId($addressId);
        }
        return $this;
    }
}
