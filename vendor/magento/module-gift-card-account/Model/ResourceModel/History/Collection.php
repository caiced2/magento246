<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\ResourceModel\History;

/**
 * GiftCardAccount History Resource Collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Resource constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\GiftCardAccount\Model\History::class,
            \Magento\GiftCardAccount\Model\ResourceModel\History::class
        );
    }
}
