<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Helper\Product\Flat;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class FlatColumnsDefinition
 */
class FlatColumnsDefinition
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
    public function afterGetFlatColumnsDdlDefinition(
        \Magento\Catalog\Helper\Product\Flat\Indexer $subject,
        array $result
    ) {
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $result[$linkField] = [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            'length' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => false,
            'comment' => 'Row Id',
        ];

        return $result;
    }
}
