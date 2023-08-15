<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Address;

/**
 * Customer Sales Address abstract resource
 */
abstract class AbstractAddress extends \Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\AbstractSales
{
    /**
     * Used us prefix to name of column table
     *
     * @var null | string
     */
    protected $_columnPrefix = null;

    /**
     * Attach data to models
     *
     * @param \Magento\Framework\DataObject[] $entities
     * @return $this
     */
    public function attachDataToEntities(array $entities)
    {
        $items = [];
        $itemIds = [];
        foreach ($entities as $item) {
            /** @var $item \Magento\Framework\DataObject */
            $itemIds[] = $item->getId();
            $items[$item->getId()] = $item;
        }

        if ($itemIds) {
            $select = $this->getConnection()->select()->from(
                $this->getMainTable()
            )->where(
                "{$this->getIdFieldName()} IN (?)",
                $itemIds
            );
            $rowSet = $this->getConnection()->fetchAll($select);
            foreach ($rowSet as $row) {
                $items[$row[$this->getIdFieldName()]]->addData($row);
            }
        }

        return $this;
    }
}
