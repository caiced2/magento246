<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model\Category;

use InvalidArgumentException;
use Magento\Catalog\Model\Category\Product\PositionResolver;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;
use Magento\VisualMerchandiser\Model\Category\Position\TemporaryTableFactory;
use Magento\VisualMerchandiser\Model\Position\Cache;
use Magento\VisualMerchandiser\Model\Resolver\QuantityAndStock;
use Magento\VisualMerchandiser\Model\Sorting;
use Zend_Db_Expr;
use Zend_Db_Select_Exception;

/**
 * This class is for manipulation products' collections and data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Products
{
    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var Manager
     */
    protected $_moduleManager;

    /**
     * @var Cache
     */
    protected $_cache;

    /**
     * @var string
     */
    protected $_cacheKey;

    /**
     * @var Sorting
     */
    protected $sorting;

    /**
     * @var PositionResolver
     */
    private $positionResolver;

    /**
     * @var QuantityAndStock
     */
    private $quantityStockResolver;

    /**
     * @var array|bool
     */
    private $positions = false;

    /**
     * @var TemporaryTableFactory
     */
    private $temporaryTableFactory;

    /**
     * @param ProductFactory $productFactory
     * @param Manager $moduleManager
     * @param Cache $cache
     * @param Sorting $sorting
     * @param QuantityAndStock $quantityStockResolver
     * @param PositionResolver|null $positionResolver
     * @param TemporaryTableFactory|null $temporaryTableFactory
     */
    public function __construct(
        ProductFactory $productFactory,
        Manager $moduleManager,
        Cache $cache,
        Sorting $sorting,
        QuantityAndStock $quantityStockResolver,
        PositionResolver $positionResolver = null,
        ?TemporaryTableFactory $temporaryTableFactory = null
    ) {
        $this->_productFactory = $productFactory;
        $this->_moduleManager = $moduleManager;
        $this->_cache = $cache;
        $this->sorting = $sorting;
        $this->quantityStockResolver = $quantityStockResolver;
        $this->positionResolver = $positionResolver ?: ObjectManager::getInstance()->get(PositionResolver::class);
        $this->temporaryTableFactory = $temporaryTableFactory
            ?: ObjectManager::getInstance()->get(TemporaryTableFactory::class);
    }

    /**
     * Sets cache key
     *
     * @param string $key
     * @return void
     */
    public function setCacheKey($key)
    {
        $this->_cacheKey = $key;
    }

    /**
     * Retrieves a product factory object
     *
     * @return ProductFactory
     */
    public function getFactory()
    {
        return $this->_productFactory;
    }

    /**
     * Builds the collection for a grid
     *
     * @param int $categoryId
     * @param int $store (Optional)
     * @param array|null $productPositions (Optional)
     * @return Collection
     * @throws LocalizedException
     * @throws InvalidArgumentException
     */
    public function getCollectionForGrid($categoryId, $store = null, $productPositions = null)
    {
        /** @var Collection $collection */
        $collection = $this->getFactory()->create()->getCollection()
            ->addAttributeToSelect(
                [
                    'sku',
                    'name',
                    'price',
                    'small_image'
                ]
            );

        $collection = $this->quantityStockResolver->joinStock($collection);

        if (!is_array($this->_cache->getPositions($this->_cacheKey)) && !is_array($productPositions)) {
            $collection->getSelect()->where('at_position.category_id = ?', $categoryId);
            $collection->joinField(
                'position',
                'catalog_category_product',
                'position',
                'product_id=entity_id',
                null,
                'left'
            );
            $collection->setOrder('position', $collection::SORT_ORDER_ASC);
            $this->positions = $this->positionResolver->getPositions($categoryId);
            $collection->setOrder('entity_id', $collection::SORT_ORDER_DESC);
        } else {
            if (is_array($productPositions)) {
                $this->positions = $productPositions;
            }
            $collection->joinField(
                'position',
                $this->temporaryTableFactory->create($this->getPositions()),
                'position',
                'product_id=entity_id',
            );
            $collection->getSelect()->order('at_position.position ' . $collection::SORT_ORDER_ASC);
            $collection = $this->applySortingFromCache($collection);
        }

        if ($store !== null) {
            $collection->addStoreFilter($store);
        }

        return $collection;
    }

    /**
     * Returns the default stock id
     *
     * @return int
     */
    protected function getStockId()
    {
        return Stock::DEFAULT_STOCK_ID;
    }

    /**
     * Save positions from collection to cache
     *
     * @param Collection $collection
     * @return void
     * @throws LocalizedException
     * @throws Zend_Db_Select_Exception
     */
    public function savePositions(Collection $collection)
    {
        if (!$collection->isLoaded()) {
            $collection->load();
        }
        $select = clone $collection->getSelect();

        $select->reset(Select::LIMIT_COUNT);
        $select->reset(Select::LIMIT_OFFSET);
        $this->prependColumn($select, $collection->getEntity()->getIdFieldName());

        $positions = array_flip($collection->getConnection()->fetchCol($select));

        $this->savePositionsToCache($positions);
    }

    /**
     * Add needed column to the Select on the first position
     *
     * There are no problems for MySQL with several same columns in the result set
     *
     * @param Select $select
     * @param string $columnName
     * @return void
     * @throws Zend_Db_Select_Exception
     */
    private function prependColumn(Select $select, string $columnName)
    {
        $columns = $select->getPart(Select::COLUMNS);
        array_unshift($columns, ['e', $columnName, null]);
        $select->setPart(Select::COLUMNS, $columns);
    }

    /**
     * Apply cached positions, sort order products returns a base collection with WHERE IN filter applied
     *
     * @param Collection $collection
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function applyCachedChanges(Collection $collection)
    {
        $positions = $this->getPositions();
        if (!$positions) {
            return $collection;
        }

        $collection->getSelect()->reset(Select::ORDER);
        asort($positions, SORT_NUMERIC);

        $ids = implode(',', array_keys($positions));
        $select = $collection->getSelect();
        $field = $select->getAdapter()->quoteIdentifier('e.entity_id');
        $orderExpression = new Zend_Db_Expr("FIELD({$field}, {$ids})");
        $select->order($orderExpression);

        return $this->applySortingFromCache($collection);
    }

    /**
     * Save products positions to cache
     *
     * @param array $positions
     * @return void
     */
    protected function savePositionsToCache($positions)
    {
        $this->_cache->saveData(
            $this->_cacheKey,
            $positions
        );
    }

    /**
     * Retrieves positions
     *
     * @return array|bool
     * @throws InvalidArgumentException
     */
    private function getPositions()
    {
        $positions = $this->_cache->getPositions($this->_cacheKey);
        return is_array($positions) ? $positions : $this->positions;
    }

    /**
     * Apply sorting from cache
     *
     * @param Collection $collection
     * @return Collection
     */
    private function applySortingFromCache(Collection $collection): Collection
    {
        $sortOrder = $this->_cache->getSortOrder($this->_cacheKey);
        $sortBuilder = $this->sorting->getSortingInstance($sortOrder);

        return $sortBuilder->sort($collection);
    }
}
