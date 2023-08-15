<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Model\Page;

use Magento\Cms\Model\Page\DataProvider as CmsDataProvider;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Staging\Model\Entity\DataProvider\MetadataProvider;

/**
 * Class DataProvider
 */
class DataProvider extends CmsDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $pageCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param MetadataProvider $metadataProvider
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $pageCollectionFactory,
        DataPersistorInterface $dataPersistor,
        MetadataProvider $metadataProvider,
        array $meta = [],
        array $data = []
    ) {
        $meta = array_replace_recursive($meta, $metadataProvider->getMetadata());
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $pageCollectionFactory,
            $dataPersistor,
            $meta,
            $data
        );
    }
}
