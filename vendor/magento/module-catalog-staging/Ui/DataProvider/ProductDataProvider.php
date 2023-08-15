<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Ui\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Model\VersionManager;

class ProductDataProvider extends \Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Ui\DataProvider\AddFieldToCollectionInterface[] $addFieldStrategies
     * @param \Magento\Ui\DataProvider\AddFilterToCollectionInterface[] $addFilterStrategies
     * @param RequestInterface $request
     * @param VersionManager $versionManager
     * @param array $meta
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        $addFieldStrategies,
        $addFilterStrategies,
        RequestInterface $request,
        VersionManager $versionManager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $collectionFactory,
            $addFieldStrategies,
            $addFilterStrategies,
            $meta,
            $data
        );

        $this->request = $request;
        $this->versionManager = $versionManager;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $updateId = $this->request->getParam('update_id');

        if ($updateId) {
            $this->versionManager->setCurrentVersionId($updateId);
            $select = $this->getCollection()->getSelect();
            $select->setPart('disable_staging_preview', true);
            $select->where('created_in = ?', (int)$updateId);
            return parent::getData();
        }

        return [
            'totalRecords' => 0,
            'items' => []
        ];
    }
}
