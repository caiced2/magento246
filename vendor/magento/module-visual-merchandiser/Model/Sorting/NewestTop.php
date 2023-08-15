<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Sorting;

class NewestTop extends AttributeAbstract
{
    /**
     * @return string
     */
    protected function getSortField()
    {
        return 'entity_id';
    }

    /**
     * @return string
     */
    protected function getSortDirection()
    {
        return $this->descOrder();
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return __("Newest products first");
    }
}
