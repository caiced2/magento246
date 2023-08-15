<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Model\Indexer\Product;

use Magento\CatalogPermissions\Model\Indexer\Product\Action\ProductSelectDataProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Query\Generator as QueryGenerator;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\CatalogPermissions\Model\Indexer\Category;

/**
 * Product permissions index filler
 */
class IndexFiller
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var QueryGenerator
     */
    private $queryGenerator;

    /**
     * @var ProductSelectDataProvider
     */
    private $selectDataProvider;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var DeploymentConfig|null
     */
    private $deploymentConfig;

    /**
     * Deployment config path
     *
     * @var string
     */
    private const DEPLOYMENT_CONFIG_INDEXER_BATCHES = 'indexer/batch_size/';

    /**
     * @param ResourceConnection $resource
     * @param QueryGenerator $queryGenerator
     * @param ProductSelectDataProvider $selectDataProvider
     * @param int $batchSize
     * @param string $connectionName
     * @param DeploymentConfig|null $deploymentConfig
     */
    public function __construct(
        ResourceConnection $resource,
        QueryGenerator $queryGenerator,
        ProductSelectDataProvider $selectDataProvider,
        int $batchSize = 10000,
        string $connectionName = 'indexer',
        ?DeploymentConfig $deploymentConfig = null
    ) {
        $this->resource = $resource;
        $this->queryGenerator = $queryGenerator;
        $this->selectDataProvider = $selectDataProvider;
        $this->batchSize = $batchSize;
        $this->connection = $this->resource->getConnection($connectionName);
        $this->deploymentConfig = $deploymentConfig ?: ObjectManager::getInstance()->get(DeploymentConfig::class);
    }

    /**
     * Populate product permissions index table
     *
     * @param StoreInterface $store
     * @param int $customerGroupId
     * @param string $categoryPermissionsTable
     * @param string $productPermissionsTable
     * @param array $productIds
     * @return void
     */
    public function populate(
        StoreInterface $store,
        int $customerGroupId,
        string $categoryPermissionsTable,
        string $productPermissionsTable,
        array $productIds = []
    ): void {
        $this->batchSize = $this->deploymentConfig->get(
            self::DEPLOYMENT_CONFIG_INDEXER_BATCHES . Category::INDEXER_ID
        ) ?? $this->batchSize;

        $select = $this->selectDataProvider->getSelect(
            $customerGroupId,
            $store,
            $categoryPermissionsTable,
            $productIds
        );
        $batchIterator = $this->queryGenerator->generate('product_id', $select, $this->batchSize);
        foreach ($batchIterator as $batchSelect) {
            $sql = $this->connection->insertFromSelect(
                $batchSelect,
                $productPermissionsTable,
                [],
                AdapterInterface::REPLACE
            );
            $this->connection->query($sql);
        }
    }
}
