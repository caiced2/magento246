<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product\Attribute;

use Magento\Eav\Model\Entity\Attribute\UniqueValidationInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\DataObject;
use Magento\Eav\Model\Entity\AbstractEntity;

/**
 * Class for validate unique attribute value
 */
class UniqueValidator implements UniqueValidationInterface
{
    /**
     * @inheritdoc
     *
     * Additional validation rule when Scheduled Update is active
     */
    public function validate(
        AbstractAttribute $attribute,
        DataObject $object,
        AbstractEntity $entity,
        $entityLinkField,
        array $entityIds
    ) {
        $result = true;
        $connection = $entity->getConnection();
        $select = $connection->select();
        $entityIdField = $entity->getEntityIdField();
        $select->from(
            $entity->getEntityTable(),
            $entityLinkField
        );
        $select->setPart('disable_staging_preview', true);
        $select->where(
            $entityIdField . ' = :value'
        );
        $entityId = $object->getEntityId();
        $bind = ['value' => ($entityId !== null) ? trim($entityId) : ''];
        $relatedRowIds = $connection->fetchCol($select, $bind);

        foreach ($entityIds as $datum) {
            if (!in_array($datum, $relatedRowIds)) {
                $result = false;
            }
        }

        return $result;
    }
}
