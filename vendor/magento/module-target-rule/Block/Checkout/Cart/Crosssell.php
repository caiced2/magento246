<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Block\Checkout\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TargetRule\Model\Rotation;

/**
 * TargetRule Checkout Cart Cross-Sell Products Block
 *
 * @api
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @since 100.0.2
 */
class Crosssell extends \Magento\TargetRule\Block\Product\AbstractProduct
{
    /**
     * Array of product objects in cart
     *
     * @var array
     */
    protected $_products;

    /**
     * Object of just added product to cart
     *
     * @var \Magento\Catalog\Model\Product
     */
    protected $_lastAddedProduct;

    /**
     * Whether get products by last added
     *
     * @var bool
     */
    protected $_byLastAddedProduct = false;

    /**
     * @var \Magento\TargetRule\Model\Index
     */
    protected $_index;

    /**
     * @var \Magento\TargetRule\Model\IndexFactory
     */
    protected $_indexFactory;

    /**
     * @var \Magento\Catalog\Model\Product\LinkFactory
     */
    protected $_productLinkFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_visibility;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $productTypeConfig;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var Rotation
     */
    private $rotation;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\TargetRule\Model\ResourceModel\Index $index
     * @param \Magento\TargetRule\Helper\Data $targetRuleData
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\Product\Visibility $visibility
     * @param \Magento\CatalogInventory\Helper\Stock $stockHelper
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory
     * @param \Magento\TargetRule\Model\IndexFactory $indexFactory
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     * @param Rotation|null $rotation
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\TargetRule\Model\ResourceModel\Index $index,
        \Magento\TargetRule\Helper\Data $targetRuleData,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Checkout\Model\Session $session,
        \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory,
        \Magento\TargetRule\Model\IndexFactory $indexFactory,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        ProductRepositoryInterface $productRepository,
        array $data = [],
        Rotation $rotation = null
    ) {
        $this->productTypeConfig = $productTypeConfig;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_visibility = $visibility;
        $this->stockHelper = $stockHelper;
        $this->_checkoutSession = $session;
        $this->_productLinkFactory = $productLinkFactory;
        $this->_indexFactory = $indexFactory;
        $this->rotation = $rotation ?? ObjectManager::getInstance()->get(Rotation::class);
        parent::__construct(
            $context,
            $index,
            $targetRuleData,
            $data
        );
        $this->_isScopePrivate = true;
        $this->productRepository = $productRepository;
    }

    /**
     * Slice items to limit
     *
     * @return $this
     * @since 100.1.0
     */
    protected function _sliceItems()
    {
        if ($this->_items !== null) {
            $this->_items = array_slice($this->_items, 0, $this->getPositionLimit(), true);
        }
        return $this;
    }

    /**
     * Retrieve Catalog Product List Type identifier
     *
     * @return int
     */
    public function getProductListType()
    {
        return \Magento\TargetRule\Model\Rule::CROSS_SELLS;
    }

    /**
     * Retrieve just added to cart product id
     *
     * @return int|false
     */
    public function getLastAddedProductId()
    {
        return $this->_checkoutSession->getLastAddedProductId(true);
    }

    /**
     * Retrieve just added to cart product object
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getLastAddedProduct()
    {
        if ($this->_lastAddedProduct === null) {
            $productId = $this->getLastAddedProductId();
            if ($productId) {
                try {
                    $this->_lastAddedProduct = $this->productRepository->getById($productId);
                } catch (NoSuchEntityException $e) {
                    $this->_lastAddedProduct = false;
                }
            } else {
                $this->_lastAddedProduct = false;
            }
        }
        return $this->_lastAddedProduct;
    }

    /**
     * Retrieve quote instance
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * Retrieve Array of Product instances in Cart
     *
     * @return array
     */
    protected function _getCartProducts()
    {
        if ($this->_products === null) {
            $this->_products = [];
            foreach ($this->getQuote()->getAllItems() as $quoteItem) {
                /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
                $product = $quoteItem->getProduct();
                $this->_products[$product->getEntityId()] = $product;
            }
        }

        return $this->_products;
    }

    /**
     * Retrieve Array of product ids in Cart
     *
     * @return array
     */
    protected function _getCartProductIds()
    {
        $products = $this->_getCartProducts();
        return array_keys($products);
    }

    /**
     * Retrieve Array of product ids which have special relation with products in Cart.
     *
     * For example simple product as part of product type that represents product set
     *
     * @return array
     */
    protected function _getCartProductIdsRel()
    {
        $productIds = [];
        foreach ($this->getQuote()->getAllItems() as $quoteItem) {
            $productTypeOpt = $quoteItem->getOptionByCode('product_type');
            if ($productTypeOpt instanceof \Magento\Quote\Model\Quote\Item\Option &&
                $this->productTypeConfig->isProductSet(
                    $productTypeOpt->getValue()
                ) && $productTypeOpt->getProductId()
            ) {
                $productIds[] = $productTypeOpt->getProductId();
            }
        }

        return $productIds;
    }

    /**
     * Retrieve Target Rule Index instance
     *
     * @return \Magento\TargetRule\Model\Index
     */
    protected function _getTargetRuleIndex()
    {
        if ($this->_index === null) {
            $this->_index = $this->_indexFactory->create();
        }
        return $this->_index;
    }

    /**
     * Retrieve Maximum Number Of Product
     *
     * @return int
     */
    public function getPositionLimit()
    {
        return $this->_targetRuleData->getMaximumNumberOfProduct($this->getProductListType());
    }

    /**
     * Retrieve Position Behavior
     *
     * @return int
     */
    public function getPositionBehavior()
    {
        return $this->_targetRuleData->getShowProducts($this->getProductListType());
    }

    /**
     * Get link collection for cross-sell
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection|null
     */
    protected function _getTargetLinkCollection()
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection */
        $collection = $this->_productLinkFactory->create()
            ->useCrossSellLinks()
            ->getProductCollection()
            ->setStoreId($this->_storeManager->getStore()->getId())
            ->setPageSize($this->getPositionLimit())
            ->setGroupBy();
        $this->_addProductAttributesAndPrices($collection);
        $collection->setVisibility($this->_visibility->getVisibleInSiteIds());

        return $collection;
    }

    /**
     * Retrieve array of cross-sell products for just added product to cart
     *
     * @return array
     */
    protected function _getProductsByLastAddedProduct()
    {
        $product = $this->getLastAddedProduct();
        if (!$product) {
            return [];
        }
        $this->_byLastAddedProduct = true;
        $items = parent::getItemCollection();
        $this->_byLastAddedProduct = false;
        $this->_items = null;
        return $items;
    }

    /**
     * Retrieve Product Ids from Cross-sell rules based products index by product object
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $count
     * @param array $excludeProductIds
     * @return array
     */
    protected function _getProductIdsFromIndexByProduct($product, $count, $excludeProductIds = [])
    {
        return $this->_getTargetRuleIndex()->setType(
            $this->getProductListType()
        )->setLimit(
            $count
        )->setProduct(
            $product
        )->setExcludeProductIds(
            $excludeProductIds
        )->getProductIds();
    }

    /**
     * Retrieve Product Collection by Product Ids
     *
     * @param array $productIds
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getProductCollectionByIds($productIds)
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $collection = $this->_productCollectionFactory->create();
        $collection->addFieldToFilter('entity_id', ['in' => $productIds]);
        $this->_addProductAttributesAndPrices($collection);

        $collection->setVisibility($this->_visibility->getVisibleInCatalogIds());

        return $collection;
    }

    /**
     * Retrieve Product Ids from Cross-sell rules based products index by products in shopping cart
     *
     * @param int $limit
     * @param array $excludeProductIds
     * @return array
     */
    protected function _getProductIdsFromIndexForCartProducts($limit, $excludeProductIds = [])
    {
        $resultIds = [];

        foreach ($this->_getCartProducts() as $product) {
            if ($product->getEntityId() == $this->getLastAddedProductId()) {
                continue;
            }

            $productIds = $this->_getProductIdsFromIndexByProduct(
                $product,
                $limit,
                $excludeProductIds
            );

            foreach ($productIds as $productId => $priority) {
                if (!isset($resultIds[$productId]) || $resultIds[$productId] < $priority) {
                    $resultIds[$productId] = $priority;
                }
            }
        }

        return $resultIds;
    }

    /**
     * Get exclude product ids
     *
     * @return array
     */
    protected function _getExcludeProductIds()
    {
        $excludeProductIds = $this->_getCartProductIds();
        if ($this->_items !== null) {
            $excludeProductIds = array_merge(array_keys($this->_items), $excludeProductIds);
        }
        return $excludeProductIds;
    }

    /**
     * Get target rule based products for cross-sell
     *
     * @return array
     */
    protected function _getTargetRuleProducts()
    {
        $excludeProductIds = $this->_getExcludeProductIds();
        $limit = $this->getPositionLimit();
        $productIds = $this->_byLastAddedProduct ? $this->_getProductIdsFromIndexByProduct(
            $this->getLastAddedProduct(),
            $limit,
            $excludeProductIds
        ) : $this->_getProductIdsFromIndexForCartProducts(
            $limit,
            $excludeProductIds
        );
        if (!empty($this->_items)) {
            $limit -= count($this->_items);
        }
        $productIds = $this->rotation->reorder(
            $productIds,
            $this->_targetRuleData->getRotationMode($this->getProductListType()),
            $limit
        );

        $items = [];
        if ($productIds) {
            $collection = $this->_getProductCollectionByIds(array_keys($productIds));
            foreach ($collection as $product) {
                $product->setPriority($productIds[$product->getEntityId()]);
                $items[$product->getEntityId()] = $product;
            }

            $orderedItems = [];
            foreach (array_keys($productIds) as $productId) {
                if (isset($items[$productId])) {
                    $orderedItems[$productId] = $items[$productId];
                }
            }
            $items = $orderedItems;
        }

        return $items;
    }

    /**
     * Get linked products
     *
     * @return array
     */
    protected function _getLinkProducts()
    {
        $items = [];
        $collection = $this->getLinkCollection();
        if ($collection) {
            if ($this->_byLastAddedProduct) {
                $collection->addProductFilter($this->getLastAddedProductLinkId());
            } else {
                $filterProductIds = array_merge($this->getCartProductLinkIds(), $this->getCartRelatedProductLinkIds());
                $collection->addProductFilter($filterProductIds);
            }
            $collection->addExcludeProductFilter($this->_getExcludeProductIds());

            foreach ($collection as $product) {
                $items[$product->getEntityId()] = $product;
            }
        }
        return $items;
    }

    /**
     * Retrieve array of cross-sell products
     *
     * @return array
     */
    public function getItemCollection()
    {
        if ($this->_items === null) {
            // if has just added product to cart - load cross-sell products for it
            $productsByLastAdded = $this->_getProductsByLastAddedProduct();
            $limit = $this->getPositionLimit();
            if (!empty($this->_getCartProducts()) && count($productsByLastAdded) < $limit) {
                // reset collection
                $this->_linkCollection = null;
                parent::getItemCollection();
                // products by last added are preferable
                $this->_items = $productsByLastAdded + $this->_items;
                $this->_sliceItems();
            } else {
                $this->_items = $productsByLastAdded;
            }
            $this->_sliceItems();
        }
        return $this->_items;
    }

    /**
     * Check is has items
     *
     * @return bool
     */
    public function hasItems()
    {
        return $this->getItemsCount() > 0;
    }

    /**
     * Retrieve count of product in collection
     *
     * @return int
     */
    public function getItemsCount()
    {
        return count($this->getItemCollection());
    }

    /**
     * Get cart products link IDs
     *
     * @return array
     */
    private function getCartProductLinkIds(): array
    {
        $linkField = $this->getProductLinkField();
        $linkIds = [];
        foreach ($this->_getCartProducts() as $product) {
            /** * @var Product $product */
            $linkIds[] = $product->getData($linkField);
        }
        return $linkIds;
    }

    /**
     * Get last product added to cart link ID
     *
     * @return int
     */
    private function getLastAddedProductLinkId(): int
    {
        $linkField = $this->getProductLinkField();
        return (int) $this->getLastAddedProduct()->getData($linkField);
    }

    /**
     * Get product link ID field
     *
     * @return string
     */
    private function getProductLinkField(): string
    {
        /* @var $collection Collection */
        $collection = $this->_productCollectionFactory->create();
        return $collection->getProductEntityMetadata()->getLinkField();
    }

    /**
     * Get cart related products link IDs
     *
     * @return array
     */
    private function getCartRelatedProductLinkIds(): array
    {
        $productIds = $this->_getCartProductIdsRel();
        $linkIds = [];
        if (!empty($productIds)) {
            $linkField = $this->getProductLinkField();
            /* @var $collection Collection */
            $collection = $this->_productCollectionFactory->create();
            $collection->addIdFilter($productIds);
            foreach ($collection as $product) {
                /** * @var Product $product */
                $linkIds[] = $product->getData($linkField);
            }
        }
        return $linkIds;
    }
}
