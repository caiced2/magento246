<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Helper\Product\Flat;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class FlatIndexes
 */
class FlatIndexes
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * Indexer constructor.
     *
     * @param MetadataPool $metadataPool
     */
    public function __construct(MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param \Magento\Catalog\Helper\Product\Flat\Indexer $subject
     * @param array $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return array
     */
    public function afterGetFlatIndexes(
        \Magento\Catalog\Helper\Product\Flat\Indexer $subject,
        array $result
    ) {
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $result['IDX_LINK_ID'] = [
            'type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX,
            'fields' => [$linkField],
        ];
        
        return $result;
    }
}
