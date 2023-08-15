<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedRule\Model\Condition;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedRule\Model\Condition\FilterInterface;

/**
 * Class FilterGroup
 *
 * @codeCoverageIgnore
 */
class FilterGroup extends \Magento\Framework\DataObject implements FilterGroupInterface
{
    const KEY_FILTERS = 'filters';

    /**
     * @return FilterInterface[]
     */
    public function getFilters()
    {
        return $this->getData(self::KEY_FILTERS);
    }

    /**
     * @param FilterInterface[] $filters
     * @return $this
     */
    public function setFilters($filters)
    {
        return $this->setData(self::KEY_FILTERS, $filters);
    }
}
