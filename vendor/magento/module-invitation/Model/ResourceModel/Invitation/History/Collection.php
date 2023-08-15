<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Model\ResourceModel\Invitation\History;

/**
 * Invitation status history collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Intialize collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\Invitation\Model\Invitation\History::class,
            \Magento\Invitation\Model\ResourceModel\Invitation\History::class
        );
    }
}
