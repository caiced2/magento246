<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedRule\Model\Condition;

/**
 * Interface \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface
 *
 * @api
 */
interface FilterableConditionInterface
{
    /**
     * Whether this condition can be filtered using index table
     *
     * @return bool
     */
    public function isFilterable();

    /**
     * Return a list of filter groups that represent this condition
     *
     * @return FilterGroupInterface[]
     */
    public function getFilterGroups();
}
