<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Sorting;

use Magento\Catalog\Model\Indexer\Product\Price\DimensionCollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver;
use Magento\Customer\Model\GroupManagement;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Customer\Model\Indexer\CustomerGroupDimensionProvider;
use Magento\Framework\Indexer\DimensionFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\Indexer\WebsiteDimensionProvider;

/**
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SortAbstract
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DimensionCollectionFactory
     */
    private $dimensionCollectionFactory;

    /**
     * @var IndexScopeResolver
     */
    private $indexScopeResolver;

    /**
     * @var DimensionFactory|null
     */
    private $dimensionFactory;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var PriceTableResolver
     */
    private $priceTableResolver;

    /**
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param DimensionCollectionFactory|null $dimensionCollectionFactory
     * @param IndexScopeResolver|null $indexScopeResolver
     * @param DimensionFactory|null $dimensionFactory
     * @param WebsiteRepositoryInterface|null $websiteRepository
     * @param PriceTableResolver|null $priceTableResolver
     */
    public function __construct(
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        DimensionCollectionFactory $dimensionCollectionFactory = null,
        IndexScopeResolver $indexScopeResolver = null,
        DimensionFactory $dimensionFactory = null,
        WebsiteRepositoryInterface $websiteRepository = null,
        PriceTableResolver $priceTableResolver = null
    ) {
        $this->moduleManager = $moduleManager;
        $this->scopeConfig = $scopeConfig;
        $this->dimensionCollectionFactory = $dimensionCollectionFactory
            ?? ObjectManager::getInstance()->get(DimensionCollectionFactory::class);
        $this->indexScopeResolver = $indexScopeResolver
            ?? ObjectManager::getInstance()->get(IndexScopeResolver::class);
        $this->dimensionFactory = $dimensionFactory
            ?? ObjectManager::getInstance()->get(DimensionFactory::class);
        $this->websiteRepository = $websiteRepository
            ?? ObjectManager::getInstance()->get(WebsiteRepositoryInterface::class);
        $this->priceTableResolver = $priceTableResolver
            ?? ObjectManager::getInstance()->get(PriceTableResolver::class);
    }

    /**
     * Get stock id
     *
     * @return int
     */
    protected function getStockId()
    {
        return \Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID;
    }

    /**
     * Get asc order
     *
     * @return string
     */
    protected function ascOrder()
    {
        return \Magento\Framework\DB\Select::SQL_ASC;
    }

    /**
     * Get desc order
     *
     * @return string
     */
    protected function descOrder()
    {
        return \Magento\Framework\DB\Select::SQL_DESC;
    }

    /**
     * Add price data
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    protected function addPriceData($collection)
    {
        $connection = $collection->getConnection();
        $select = $collection->getSelect();

        $websiteId = $this->websiteRepository->getDefault()->getId();
        $joinCond = join(
            ' AND ',
            [
                'price_index.entity_id = e.entity_id',
                'price_index.customer_group_id = ' . GroupManagement::NOT_LOGGED_IN_ID,
                'price_index.website_id = ' . $websiteId,
            ]
        );

        $fromPart = $select->getPart(\Magento\Framework\DB\Select::FROM);

        if (!isset($fromPart['price_index'])) {
            $least = $connection->getLeastSql(['price_index.min_price', 'price_index.tier_price']);
            $minimalExpr = $connection->getCheckSql(
                'price_index.tier_price IS NOT NULL',
                $least,
                'price_index.min_price'
            );
            $specialCheck = $connection->getCheckSql(
                'price_index.price <> price_index.final_price',
                1,
                0
            );
            $colls = [
                'price',
                'tax_class_id',
                'final_price',
                'min_price',
                'max_price',
                'tier_price',
                'special_price' => $specialCheck,
                'minimal_price' => $minimalExpr,
            ];

            $priceIndexTable = $this->priceTableResolver->resolve(
                'catalog_product_index_price',
                [
                    $this->dimensionFactory->create(
                        CustomerGroupDimensionProvider::DIMENSION_NAME,
                        (string) GroupManagement::NOT_LOGGED_IN_ID
                    ),
                    $this->dimensionFactory->create(
                        WebsiteDimensionProvider::DIMENSION_NAME,
                        (string) $websiteId
                    )
                ]
            );
            $select->joinLeft(
                ['price_index' => $priceIndexTable],
                $joinCond,
                $colls
            );
        } else {
            $fromPart['price_index']['joinCondition'] = $joinCond;
            $select->setPart(\Magento\Framework\DB\Select::FROM, $fromPart);
        }
    }
}
