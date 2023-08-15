<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Model\ResourceModel\Rule;

/**
 * Class DateApplier
 * skips adding the dates for SalesRuleStaging
 */
class DateApplier extends \Magento\SalesRule\Model\ResourceModel\Rule\DateApplier
{
    /**
     * @param \Magento\Framework\DB\Select $select
     * @param int|string $now
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function applyDate($select, $now)
    {
        return;
    }
}
