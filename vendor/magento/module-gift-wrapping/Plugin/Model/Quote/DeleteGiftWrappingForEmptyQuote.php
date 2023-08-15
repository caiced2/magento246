<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Plugin\Model\Quote;

use Magento\Quote\Model\Quote;

/**
 * Class DeleteGiftWrappingForEmptyQuote clears the gift wrapping for order when all items are removed from the cart
 */
class DeleteGiftWrappingForEmptyQuote
{
    /**
     * Clears the gift wrapping for order when all items are removed from the cart
     *
     * @param Quote $subject
     * @param Quote $result
     * @param int $itemId
     * @return Quote
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRemoveItem(Quote $subject, Quote $result, $itemId): Quote
    {
        if (!$result->hasItems() && $result->getGwId() !== null) {
            $result->setGwId(null);
            $result->setGwPrice(0);
            $result->setGwBasePrice(0);
        }

        return $result;
    }
}
