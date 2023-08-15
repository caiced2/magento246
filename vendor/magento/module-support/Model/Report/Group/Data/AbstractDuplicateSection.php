<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Data;

use Magento\Framework\EntityManager\EntityMetadata;

/**
 * General abstract class for Report "Duplicate Products/Categories by URL and SKU"
 */
abstract class AbstractDuplicateSection extends AbstractDataGroup
{
    /**
     * Get attribute id by attribute code
     *
     * @param string $attributeCode
     * @param int $entityTypeId
     * @return int
     */
    protected function getAttributeId($attributeCode, $entityTypeId)
    {
        $sql = 'SELECT `attribute_id`'
            . ' FROM `' . $this->resource->getTable('eav_attribute') . '`'
            . ' WHERE `attribute_code` = "' . $attributeCode . '" AND `entity_type_id` = ' . $entityTypeId;
        return (int)$this->connection->fetchOne($sql);
    }

    /**
     * Get information about duplicates by attribute id
     *
     * @param int $attributeId
     * @param EntityMetadata $entityMetadata
     * @param string $entityVarcharTable Table name, processes by resource->getTable
     * @return array
     */
    protected function getInfoDuplicateAttributeById($attributeId, EntityMetadata $entityMetadata, $entityVarcharTable)
    {
        $linkField = $entityMetadata->getLinkField();

        $select = $this->connection->select()
            ->from(['e' => $entityMetadata->getEntityTable()], [])
            ->join(['attr' => $entityVarcharTable], "attr.{$linkField} = e.{$linkField}", [])
            ->columns(['cnt' => new \Zend_Db_Expr("COUNT(DISTINCT e.{$linkField})"), 'value' => 'attr.value'])
            ->where('attr.attribute_id = ?', $attributeId)
            ->group('attr.value')
            ->having('cnt > ?', 1)
            ->order('cnt DESC')
            ->order('e.entity_id');

        return $this->connection->fetchAll($select);
    }

    /**
     * Get information about duplicates by SKU
     *
     * @param string $entityTable
     * @return array
     */
    protected function getInfoDuplicateSku($entityTable)
    {
        $select = $this->connection->select()
            ->from($entityTable, ['cnt' =>  new \Zend_Db_Expr('COUNT(1)'), 'sku' => 'sku'])
            ->group('sku')
            ->having('cnt > ?', 1)
            ->order('cnt DESC')
            ->order('entity_id');
        return $this->connection->fetchAll($select);
    }
}
