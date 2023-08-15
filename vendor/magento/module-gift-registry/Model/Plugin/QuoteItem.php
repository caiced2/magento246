<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\Plugin;

use Magento\Quote\Model\Quote\Item\ToOrderItem as QuoteToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem as AbstractQuoteItem;
use Magento\Quote\Model\Quote\Address\Item as QuoteAddressItem;

/**
 * Plugin for Magento\Quote\Model\Quote\Item\ToOrderItem
 */
class QuoteItem
{
    /**
     * Transfer gift registry id from quote to order
     *
     * @param QuoteToOrderItem $subject
     * @param OrderItemInterface $result
     * @param AbstractQuoteItem $item
     * @param array $data
     * @return OrderItemInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterConvert(
        QuoteToOrderItem $subject,
        OrderItemInterface $result,
        AbstractQuoteItem $item,
        $data = []
    ) {
        $registryItemId = $item instanceof QuoteAddressItem
            ? $item->getQuoteItem()->getGiftregistryItemId()
            : $item->getGiftregistryItemId();

        if ($registryItemId) {
            $result->setGiftregistryItemId($registryItemId);
        }

        return $result;
    }
}
