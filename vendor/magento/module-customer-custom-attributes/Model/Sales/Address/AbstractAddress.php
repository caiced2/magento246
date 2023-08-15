<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Model\Sales\Address;

/**
 * Customer Address abstract model
 *
 */
abstract class AbstractAddress extends \Magento\CustomerCustomAttributes\Model\Sales\AbstractSales
{
    /**
     * Attach data to models
     *
     * @param \Magento\Framework\DataObject[] $entities
     * @return $this
     */
    public function attachDataToEntities(array $entities)
    {
        $this->_getResource()->attachDataToEntities($entities);
        return $this;
    }
}
