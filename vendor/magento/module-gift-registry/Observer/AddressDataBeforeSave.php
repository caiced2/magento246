<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\GiftRegistry\Helper\Data;

class AddressDataBeforeSave implements ObserverInterface
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
     * Check if gift registry prefix is set for customer address id and set giftRegistryItemId
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $object = $observer->getEvent()->getDataObject();
        $addressId = $object->getCustomerAddressId();
        $prefix = $this->_giftRegistryData->getAddressIdPrefix();

        if ($addressId !== null
            && !is_numeric($addressId) && preg_match('/^' . $prefix . '([0-9]+)$/', $addressId)) {
            $object->setGiftregistryItemId(str_replace($prefix, '', $addressId));
        }
        return $this;
    }
}
