<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Plugin;

use Magento\Framework\Math\FloatComparator;
use Magento\Sales\Model\Order;

/**
 * Checks if Credit Memo can be created according to available Store Credit for the order.
 */
class CreditMemoResolver
{
    /**
     * @var FloatComparator
     */
    private $comparator;

    /**
     * @param FloatComparator $comparator
     */
    public function __construct(FloatComparator $comparator)
    {
        $this->comparator = $comparator;
    }

    /**
     * Checks if Credit Memo is available with Store Credit.
     *
     * @see Order::canCreditmemo()
     * @param Order $subject
     * @param boolean $result
     * @return boolean
     */
    public function afterCanCreditmemo(Order $subject, bool $result): bool
    {
        // process a case only if credit memo can be created
        if (!$result) {
            return $result;
        }

        // process a case only if reward points or customer balance were refunded
        if ($subject->getBaseRwrdCrrncyAmtRefunded() === null
            && $subject->getBaseCustomerBalanceRefunded() === null
        ) {
            return $result;
        }

        $totalInvoiced = $subject->getBaseTotalInvoiced()
            + $subject->getBaseRwrdCrrncyAmtInvoiced()
            + $subject->getBaseCustomerBalanceInvoiced()
            + $subject->getBaseGiftCardsInvoiced();
        $totalRefunded = $subject->getBaseTotalRefunded()
            + $subject->getBaseRwrdCrrncyAmntRefnded()
            + ($subject->getBaseCustomerBalanceInvoiced() ? $subject->getBaseCustomerBalanceRefunded() : 0)
            + $subject->getBaseGiftCardsRefunded();

        if ($this->comparator->greaterThan($totalInvoiced, $totalRefunded)) {
            return true;
        }

        // If total invoiced is equal to total refunded amount then CreditMemo can not be created anymore
        if ($this->comparator->equal($totalRefunded, $totalInvoiced)) {
            return false;
        }

        return true;
    }
}
