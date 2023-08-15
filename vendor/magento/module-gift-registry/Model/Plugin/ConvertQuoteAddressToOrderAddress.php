<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\Plugin;

use Magento\Quote\Model\Quote\Address\ToOrderAddress as QuoteToOrderAddress;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

/**
 * Plugin for Magento\Quote\Model\Quote\Address\ToOrderAddress
 */
class ConvertQuoteAddressToOrderAddress
{
    /**
     * Transfer gift registry information from quote to order
     *
     * @param QuoteToOrderAddress $subject
     * @param OrderAddressInterface $result
     * @param QuoteAddress $quoteAddress
     * @param array $data
     * @return OrderAddressInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterConvert(
        QuoteToOrderAddress $subject,
        OrderAddressInterface $result,
        QuoteAddress $quoteAddress,
        $data = []
    ) {
        if ($quoteAddress->getGiftregistryItemId()) {
            $result->setGiftregistryItemId($quoteAddress->getGiftregistryItemId());
        }

        return $result;
    }
}
