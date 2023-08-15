<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Data;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;

/**
 * Report Duplicate Categories By URL Key
 */
class DuplicateCategoriesByUrlSection extends AbstractDuplicateSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        return [
            (string)__('Duplicate Categories By URL Key') => [
                'headers' => [__('Id'), __('URL key'), __('Name'), __('Store')],
                'data' => $this->getDuplicateCategories()
            ]
        ];
    }

    /**
     * Get duplicate categories by url key
     *
     * @return array
     */
    protected function getDuplicateCategories()
    {
        $data = [];
        try {
            $entityMetadata = $this->metadataPool->getMetadata(CategoryInterface::class);
            $entityVarcharTable = $this->resource->getTable('catalog_category_entity_varchar');
            $storeTable = $this->resource->getTable('store');
            $entityTypeId = $this->eavConfig->getEntityType(Category::ENTITY)->getId();
            $nameAttributeId = $this->getAttributeId('name', $entityTypeId);
            $urlKeyAttributeId = $this->getAttributeId('url_key', $entityTypeId);

            $duplicatesList = $this->getInfoDuplicateAttributeById(
                $urlKeyAttributeId,
                $entityMetadata,
                $entityVarcharTable
            );

            $linkField = $entityMetadata->getLinkField();

            foreach ($duplicatesList as $duplicate) {
                $select = $this->connection->select()
                    ->from(
                        ['e' => $entityMetadata->getEntityTable()],
                        [
                            'e.entity_id',
                            'name' =>  'n.value',
                            'u.store_id',
                            'store_name' => 's.name'
                        ]
                    )
                    ->joinInner(['u' => $entityVarcharTable], "e.{$linkField}  = u.{$linkField}", [])
                    ->joinLeft(['s' => $storeTable], 'u.store_id = s.store_id', [])
                    ->joinLeft(
                        ['n' => $entityVarcharTable],
                        "u.{$linkField}  = n.{$linkField} AND"
                        . ' u.store_id = n.store_id AND'
                        . ' n.attribute_id = ' . $nameAttributeId,
                        []
                    )
                    ->where('u.attribute_id = ?', $urlKeyAttributeId)
                    ->where('u.value = ?', $duplicate['value']);
                $entities = $this->connection->fetchAll($select);

                foreach ($entities as $entity) {
                    $data[] = [
                        $entity['entity_id'],
                        $duplicate['value'],
                        $entity['name'],
                        $entity['store_name'] . ' {ID:' . $entity['store_id'] . '}'
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $data;
    }
}
