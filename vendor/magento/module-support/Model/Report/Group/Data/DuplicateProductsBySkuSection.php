<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Data;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;

/**
 * Report Duplicate Products By SKU
 */
class DuplicateProductsBySkuSection extends AbstractDuplicateSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        return [
            (string)__('Duplicate Products By SKU') => [
                'headers' => [__('Id'), __('SKU'), __('Name')],
                'data' => $this->getDuplicateProducts()
            ]
        ];
    }

    /**
     * Get duplicate products by sku
     *
     * @return array
     */
    protected function getDuplicateProducts()
    {
        $data = [];
        try {
            $entityVarcharTable = $this->resource->getTable('catalog_product_entity_varchar');
            $entityTable = $this->resource->getTable('catalog_product_entity');
            $entityTypeId = $this->eavConfig->getEntityType(Product::ENTITY)->getId();
            $nameAttributeId = $this->getAttributeId('name', $entityTypeId);
            $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();

            $duplicatesList = $this->getInfoDuplicateSku($entityTable);

            foreach ($duplicatesList as $duplicate) {
                $sql = $this->connection->select()->from(
                    ['e' => $entityTable],
                    ['e.entity_id', 'n.value AS name', 'e.sku']
                )->joinLeft(
                    ['n' => $entityVarcharTable],
                    "e.{$linkField} = n.{$linkField} AND n.attribute_id = {$nameAttributeId}"
                )->where('e.sku = ?', $duplicate['sku']);

                $entities = $this->connection->fetchAll($sql);

                foreach ($entities as $entity) {
                    $data[] = [
                        $entity['entity_id'],
                        $duplicate['sku'],
                        $entity['name'],
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $data;
    }
}
