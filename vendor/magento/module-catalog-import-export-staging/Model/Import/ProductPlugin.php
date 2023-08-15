<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogImportExportStaging\Model\Import;

use Magento\CatalogImportExport\Model\Import\Product as ProductImport;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Class ProductPlugin
 */
class ProductPlugin
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * ProductPlugin constructor
     *
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        MetadataPool $metadataPool
    ) {
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param ProductImport $subject
     * @param array $entityRowsIn
     * @param array $entityRowsUp
     * @return array
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveProductEntity(
        ProductImport $subject,
        array $entityRowsIn,
        array $entityRowsUp
    ) {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $insertRows = [];
        foreach ($entityRowsIn as $key => $insertRow) {
            if (empty($insertRow[$metadata->getIdentifierField()])) {
                $insertRow[$metadata->getIdentifierField()] = $metadata->generateIdentifier();
                $insertRow['created_in'] = 1;
                $insertRow['updated_in'] = VersionManager::MAX_VERSION;
            }
            $insertRows[$key] = $insertRow;
        }
        return [$insertRows, $entityRowsUp];
    }
}
