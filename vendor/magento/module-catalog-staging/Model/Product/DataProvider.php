<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Staging\Model\Entity\DataProvider\MetadataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

class DataProvider extends \Magento\Catalog\Ui\DataProvider\Product\Form\ProductDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param PoolInterface $pool
     * @param MetadataProvider $metaDataProvider
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        PoolInterface $pool,
        MetadataProvider $metaDataProvider,
        array $meta = [],
        array $data = []
    ) {
        $meta = array_replace_recursive($meta, $metaDataProvider->getMetadata());
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $collectionFactory,
            $pool,
            $meta,
            $data
        );
    }
}
