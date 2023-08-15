<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\Plugin;

use Magento\Framework\DataObject;
use Magento\GiftRegistry\Helper\Data;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;

/**
 * Plugin for adding gift registry item during updating quote item.
 */
class UpdateQuoteItem
{
    /**
     * @var Data
     */
    private $giftRegistryData;

    /**
     * @param Data $giftRegistryData
     */
    public function __construct(
        Data $giftRegistryData
    ) {
        $this->giftRegistryData = $giftRegistryData;
    }

    /**
     * Adds gift registry item to updated quote item.
     *
     * @param Quote $subject
     * @param QuoteItem $result
     * @param int $itemId
     * @param DataObject $buyRequest
     * @param DataObject|null $params
     * @return QuoteItem
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateItem(
        Quote $subject,
        QuoteItem $result,
        int $itemId,
        DataObject $buyRequest,
        ?DataObject $params = null
    ): QuoteItem {
        if (!$this->giftRegistryData->isEnabled()) {
            return $result;
        }

        $quoteItem = $subject->getItemById($itemId);
        $giftRegistryItemId = $quoteItem->getGiftregistryItemId();
        if ($giftRegistryItemId) {
            $result->setGiftregistryItemId($giftRegistryItemId);
        }

        return $result;
    }
}
