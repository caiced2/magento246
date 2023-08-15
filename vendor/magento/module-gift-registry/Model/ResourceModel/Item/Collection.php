<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\ResourceModel\Item;

use Magento\Framework\App\ObjectManager;
use Magento\GiftRegistry\Model\Item\Collection\FilterInterface;

/**
 * GiftRegistry entity item collection
 *
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var \Magento\GiftRegistry\Model\Item\OptionFactory
     */
    protected $optionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * List of product IDs
     * Contains IDs of products related to items and their options
     *
     * @var array
     */
    protected $_productIds = [];

    /**
     * @var \Magento\Quote\Model\Quote\Config
     */
    protected $salesQuoteConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FilterInterface
     */
    private $collectionFilter;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Quote\Model\Quote\Config $salesQuoteConfig
     * @param \Magento\GiftRegistry\Model\Item\OptionFactory $optionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     * @param FilterInterface|null $collectionFilter
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Quote\Model\Quote\Config $salesQuoteConfig,
        \Magento\GiftRegistry\Model\Item\OptionFactory $optionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null,
        ?FilterInterface $collectionFilter = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->salesQuoteConfig = $salesQuoteConfig;
        $this->optionFactory = $optionFactory;
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
        $this->collectionFilter = $collectionFilter ?? ObjectManager::getInstance()->get(FilterInterface::class);
    }

    /**
     * Collection initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\GiftRegistry\Model\Item::class, \Magento\GiftRegistry\Model\ResourceModel\Item::class);
    }

    /**
     * Add gift registry filter to collection
     *
     * @param int $entityId
     * @return $this
     */
    public function addRegistryFilter($entityId)
    {
        $this->getSelect()->join(
            ['e' => $this->getTable('magento_giftregistry_entity')],
            'e.entity_id = main_table.entity_id',
            'website_id'
        )->where(
            'main_table.entity_id = ?',
            (int)$entityId
        );

        return $this;
    }

    /**
     * Add product filter to collection
     *
     * @param int $productId
     * @return $this
     */
    public function addProductFilter($productId)
    {
        if ((int)$productId > 0) {
            $this->addFieldToFilter('main_table.product_id', (int)$productId);
        }

        return $this;
    }

    /**
     * Add website filter to collection
     *
     * @return $this
     */
    public function addWebsiteFilter()
    {
        $this->getSelect()->join(
            ['cpw' => $this->getTable('catalog_product_website')],
            'cpw.product_id = main_table.product_id AND cpw.website_id = e.website_id'
        );
        return $this;
    }

    /**
     * Add item filter to collection
     *
     * @param int|array $itemId
     * @return $this
     */
    public function addItemFilter($itemId)
    {
        if (is_array($itemId)) {
            $this->addFieldToFilter('item_id', ['in' => $itemId]);
        } elseif ((int)$itemId > 0) {
            $this->addFieldToFilter('item_id', (int)$itemId);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function _renderFiltersBefore()
    {
        parent::_renderFiltersBefore();

        $this->collectionFilter->execute($this);
    }

    /**
     * After load processing
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        // Assign options and products
        $this->_assignOptions();
        $this->_assignProducts();
        $this->resetItemsDataChanged();

        return $this;
    }

    /**
     * Assign options to items
     *
     * @return $this
     */
    protected function _assignOptions()
    {
        $itemIds = array_keys($this->_items);
        $optionCollection = $this->optionFactory->create()->getCollection()->addItemFilter($itemIds);
        foreach ($this as $item) {
            $item->setOptions($optionCollection->getOptionsByItem($item));
        }
        $productIds = $optionCollection->getProductIds();
        $this->_productIds = array_merge($this->_productIds, $productIds);

        return $this;
    }

    /**
     * Assign products to items and their options
     *
     * @return $this
     */
    protected function _assignProducts()
    {
        $productIds = [];
        foreach ($this as $item) {
            $productIds[] = $item->getProductId();
        }
        $this->_productIds = array_merge($this->_productIds, $productIds);

        $productCollection = $this->productFactory->create()->getCollection()->setStoreId(
            $this->storeManager->getStore()->getId()
        )->addIdFilter(
            $this->_productIds
        )->addAttributeToSelect(
            $this->salesQuoteConfig->getProductAttributes()
        )->setFlag('has_stock_status_filter')->addStoreFilter()->addUrlRewrite()->addOptionsToResult();

        foreach ($this as $item) {
            $product = $productCollection->getItemById($item->getProductId());
            if ($product) {
                $product->setCustomOptions([]);
                foreach ($item->getOptions() as $option) {
                    $option->setProduct($productCollection->getItemById($option->getProductId()));
                }
                $item->setProduct($product);
                $item->setProductName($product->getName());
                $item->setProductSku($product->getSku());
                $item->setProductPrice($product->getPrice());
            } else {
                $item->isDeleted(true);
            }
        }
        return $this;
    }

    /**
     * Update items custom price (Depends on custom options)
     *
     * @return $this
     */
    public function updateItemAttributes()
    {
        foreach ($this->getItems() as $item) {
            $product = $item->getProduct();
            $product->setSkipCheckRequiredOption(true);
            $product->getStore()->setWebsiteId($item->getWebsiteId());
            $product->setCustomOptions($item->getOptionsByCode());
            $item->setPrice($product->getFinalPrice());
            $simpleOption = $product->getCustomOption('simple_product');
            if ($simpleOption) {
                $item->setSku($simpleOption->getProduct()->getSku());
            } else {
                $item->setSku($product->getSku());
            }
        }
        return $this;
    }
}
