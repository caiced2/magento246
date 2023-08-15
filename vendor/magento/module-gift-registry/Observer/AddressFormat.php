<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Observer;

class AddressFormat
{
    /**
     * Hide customer address if it is gift registry shipping address
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function format($observer)
    {
        /** @var \Magento\Framework\DataObject $type */
        $type = $observer->getEvent()->getType();

        /** @var \Magento\Customer\Model\Address\AbstractAddress $address */
        $address = $observer->getEvent()->getAddress();

        if ($address->getGiftregistryItemId()) {
            if (!$type->getPrevFormat()) {
                $type->setPrevFormat($type->getDefaultFormat());
            }
            $type->setDefaultFormat(__("Ship to the recipient's address."));
        } elseif ($type->getPrevFormat()) {
            $type->setDefaultFormat($type->getPrevFormat());
        }
        return $this;
    }
}
