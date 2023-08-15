<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Block\Adminhtml\DataProvider;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Refund implements ArgumentInterface
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Checks if refund to Credit Store is checked.
     *
     * @return bool
     */
    public function isRefundToStoreCreditChecked(): bool
    {
        return (bool) $this->registry->registry('current_creditmemo')->getCustomerBalanceRefundFlag();
    }

    /**
     * Gets Credit Memo grand total.
     *
     * @return float
     */
    public function getCreditmemoGrandTotal(): float
    {
        return $this->registry->registry('current_creditmemo')->getBaseGrandTotal();
    }
}
