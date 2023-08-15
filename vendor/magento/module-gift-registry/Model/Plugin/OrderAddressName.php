<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\Plugin;

use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Framework\Phrase;

/**
 * Plugin for Magento\Sales\Model\Order\Address
 */
class OrderAddressName
{
    /**
     * Replace "Ship To" value for gift registry items
     *
     * @param OrderAddress $subject
     * @param string $result
     * @return Phrase|string
     */
    public function afterGetName(OrderAddress $subject, $result)
    {
        if ($subject->getGiftregistryItemId()) {
            return __('Ship to the recipient\'s address.');
        }

        return $result;
    }
}
