<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedRule\Model\Condition;

/**
 * Interface \Magento\AdvancedRule\Model\Condition\FilterGroupInterface
 *
 * @api
 */
interface FilterGroupInterface
{
    /**
     * @return FilterInterface[]
     */
    public function getFilters();

    /**
     * @param FilterInterface[] $filters
     * @return $this
     */
    public function setFilters($filters);
}
