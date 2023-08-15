<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Plugin\Model\Quote;

use Magento\Quote\Model\Quote;

/**
 * Reset customer balance usage after item removing.
 */
class ResetCustomerBalanceUsage
{
    /**
     * @param Quote $quote
     * @param Quote $result
     * @param mixed $itemId
     *
     * @return Quote
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRemoveItem(Quote $quote, Quote $result, $itemId): Quote
    {
        if (empty($result->getAllVisibleItems())) {
            $result->setUseCustomerBalance(false);
        }

        return $result;
    }
}
