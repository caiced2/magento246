<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Model\Sales;

/**
 * Customer abstract model
 *
 */
abstract class AbstractSales extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Save new attribute
     *
     * @param \Magento\Customer\Model\Attribute $attribute
     * @return $this
     */
    public function saveNewAttribute(\Magento\Customer\Model\Attribute $attribute)
    {
        $this->_getResource()->saveNewAttribute($attribute);
        return $this;
    }

    /**
     * Delete attribute
     *
     * @param \Magento\Customer\Model\Attribute $attribute
     * @return $this
     */
    public function deleteAttribute(\Magento\Customer\Model\Attribute $attribute)
    {
        $this->_getResource()->deleteAttribute($attribute);
        return $this;
    }

    /**
     * Attach extended data to sales object
     *
     * @param \Magento\Framework\Model\AbstractModel $sales
     * @return $this
     */
    public function attachAttributeData(\Magento\Framework\Model\AbstractModel $sales)
    {
        $sales->addData($this->getData());
        return $this;
    }

    /**
     * Save extended attributes data
     *
     * @param \Magento\Framework\Model\AbstractModel $sales
     * @return $this
     */
    public function saveAttributeData(\Magento\Framework\Model\AbstractModel $sales)
    {
        $this->addData($sales->getData())->setId($sales->getId())->save();

        return $this;
    }

    /**
     * Processing object before save data.
     * Need to check if main entity is already deleted from the database:
     * we should not save additional attributes for deleted entities.
     *
     * @return $this
     */
    public function beforeSave()
    {
        if ($this->_dataSaveAllowed && !$this->_getResource()->isEntityExists($this)) {
            $this->_dataSaveAllowed = false;
        }
        return parent::beforeSave();
    }
}
