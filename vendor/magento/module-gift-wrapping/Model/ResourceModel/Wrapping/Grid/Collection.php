<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\ResourceModel\Wrapping\Grid;

/**
 * Gift Wrapping Collection
 * @codeCoverageIgnore
 */
class Collection extends \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection
{
    /**
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addStoreAttributesToResult();
        $this->addWebsitesToResult();
        return $this;
    }
}
