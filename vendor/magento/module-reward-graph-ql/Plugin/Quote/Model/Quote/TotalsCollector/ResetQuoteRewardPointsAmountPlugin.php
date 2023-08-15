<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RewardGraphQl\Plugin\Quote\Model\Quote\TotalsCollector;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;

/**
 * Reset quote reward points before collect totals plugin.
 */
class ResetQuoteRewardPointsAmountPlugin
{
    /**
     * Reset quote reward points amount for correct totals calculation.
     *
     * @param TotalsCollector $subject
     * @param Quote $quote
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCollectQuoteTotals(
        TotalsCollector $subject,
        Quote $quote
    ) {
        $quote->setRewardPointsBalance(0);
        $quote->setRewardCurrencyAmount(0);
        $quote->setBaseRewardCurrencyAmount(0);
    }
}
