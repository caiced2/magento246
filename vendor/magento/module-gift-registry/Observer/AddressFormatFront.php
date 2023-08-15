<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddressFormatFront implements ObserverInterface
{
    /**
     * @var AddressFormat
     */
    protected $addressFormat;

    /**
     * @param AddressFormat $addressFormat
     */
    public function __construct(AddressFormat $addressFormat)
    {
        $this->addressFormat = $addressFormat;
    }

    /**
     * Hide customer address on the frontend if it is gift registry shipping address
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->addressFormat->format($observer);
        return $this;
    }
}
