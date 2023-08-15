<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\ResourceModel\Rules;

/**
 * Class Collection
 * @package Magento\VisualMerchandiser\Model\ResourceModel\Rules
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource table
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Magento\VisualMerchandiser\Model\Rules::class,
            \Magento\VisualMerchandiser\Model\ResourceModel\Rules::class
        );
    }
}
