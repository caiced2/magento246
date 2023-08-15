<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Data;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;

/**
 * Report Duplicate Products By URL Key
 */
class DuplicateProductsByUrlSection extends AbstractDuplicateSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        return [
            (string)__('Duplicate Products By URL Key') => [
                'headers' => [__('Id'), __('URL Key'), __('Name'), __('Website'), __('Store')],
                'data' => $this->getDuplicateProducts()
            ]
        ];
    }

    /**
     * Get duplicate products by url key
     *
     * @return array
     */
    protected function getDuplicateProducts()
    {
        $data = [];
        try {
            $entityMetadata = $this->metadataPool->getMetadata(ProductInterface::class);
            $entityVarcharTable = $this->resource->getTable('catalog_product_entity_varchar');
            $productWebsiteTable = $this->resource->getTable('catalog_product_website');
            $storeWebsiteTable = $this->resource->getTable('store_website');
            $storeTable = $this->resource->getTable('store');
            $entityTypeId = $this->eavConfig->getEntityType(Product::ENTITY)->getId();
            $nameAttributeId = $this->getAttributeId('name', $entityTypeId);
            $urlKeyAttributeId = $this->getAttributeId('url_key', $entityTypeId);
            $entityTable = $this->resource->getTable('catalog_product_entity');
            $linkField = $this->metadataPool->getMetadata(
                \Magento\Catalog\Api\Data\ProductInterface::class
            )->getLinkField();

            $duplicatesList = $this->getInfoDuplicateAttributeById(
                $urlKeyAttributeId,
                $entityMetadata,
                $entityVarcharTable
            );

            foreach ($duplicatesList as $duplicate) {
                $sql = $this->connection->select()->from(
                    ['u' => $entityVarcharTable],
                    [
                        'e.entity_id',
                        'n.value AS name',
                        'u.store_id',
                        's.name AS store_name',
                        'pw.website_id',
                        'sw.name AS website_name'
                    ]
                )->joinLeft(
                    ['e' => $entityTable],
                    "e.{$linkField} = u.{$linkField}",
                    []
                )->joinLeft(
                    ['pw' => $productWebsiteTable],
                    "e.entity_id = pw.product_id",
                    []
                )->joinLeft(
                    ['sw' => $storeWebsiteTable],
                    "pw.website_id = sw.website_id",
                    []
                )->joinLeft(
                    ['s' => $storeTable],
                    "u.store_id = s.store_id",
                    []
                )->joinLeft(
                    ['n' => $entityVarcharTable],
                    "u.{$linkField} = n.{$linkField}"
                    . " AND u.store_id = n.store_id"
                    . " AND n.attribute_id = {$nameAttributeId}",
                    []
                )->where(
                    "u.attribute_id = ?",
                    $urlKeyAttributeId
                )->where(
                    "u.value = ?",
                    $duplicate['value']
                );

                $entities = $this->connection->fetchAll($sql);

                foreach ($entities as $entity) {
                    $data[] = [
                        $entity['entity_id'],
                        $duplicate['value'],
                        $entity['name'],
                        $entity['website_id']
                            ? $entity['website_name'] . ' {ID:' . $entity['website_id'] . '}' : 'Not select',
                        $entity['store_id']
                            ? $entity['store_name'] . ' {ID:' . $entity['store_id'] . '}' : 'All'
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $data;
    }
}
