<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Block\Catalog\Product\ProductList;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\TargetRule\Block\Product\AbstractProduct;
use Magento\TargetRule\Model\Rotation;

/**
 * TargetRule Catalog Product List Abstract Block
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractProductList extends AbstractProduct implements IdentityInterface
{
    /**
     * TargetRule Index instance
     *
     * @var \Magento\TargetRule\Model\Index
     */
    protected $_currentIndex;

    /**
     * Array of exclude Product Ids
     *
     * @var array
     */
    protected $_excludeProductIds;

    /**
     * Array of all product ids in list
     *
     * @var null|array
     */
    protected $_allProductIds = null;

    /**
     * @var \Magento\TargetRule\Model\IndexFactory
     */
    protected $_indexFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_visibility;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;
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
     * @param \Magento\TargetRule\Model\IndexFactory $indexFactory
     * @param array $data
     * @param Rotation|null $rotation
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\TargetRule\Model\ResourceModel\Index $index,
        \Magento\TargetRule\Helper\Data $targetRuleData,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\TargetRule\Model\IndexFactory $indexFactory,
        array $data = [],
        Rotation $rotation = null
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_visibility = $visibility;
        $this->_indexFactory = $indexFactory;
        $this->rotation = $rotation
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Rotation::class);
        parent::__construct(
            $context,
            $index,
            $targetRuleData,
            $data
        );
    }

    /**
     * Retrieve current product instance (if actual and available)
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->_coreRegistry->registry('product');
    }

    /**
     * Retrieve Catalog Product List Type prefix without last underscore
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getTypePrefix()
    {
        switch ($this->getProductListType()) {
            case \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS:
                $prefix = 'related';
                break;

            case \Magento\TargetRule\Model\Rule::UP_SELLS:
                $prefix = 'upsell';
                break;

            default:
                throw new LocalizedException(
                    __('The Catalog Product List Type needs to be defined. Verify the type and try again.')
                );
        }
        return $prefix;
    }

    /**
     * Retrieve Target Rule Index instance
     *
     * @return \Magento\TargetRule\Model\Index
     */
    protected function _getTargetRuleIndex()
    {
        if ($this->_currentIndex === null) {
            $this->_currentIndex = $this->_indexFactory->create();
        }
        return $this->_currentIndex;
    }

    /**
     * Retrieve position limit product attribute name
     *
     * @return string
     */
    protected function _getPositionLimitField()
    {
        return sprintf('%s_tgtr_position_limit', $this->_getTypePrefix());
    }

    /**
     * Retrieve position behavior product attribute name
     *
     * @return string
     */
    protected function _getPositionBehaviorField()
    {
        return sprintf('%s_tgtr_position_behavior', $this->_getTypePrefix());
    }

    /**
     * Retrieve Maximum Number Of Product
     *
     * @return int
     */
    public function getPositionLimit()
    {
        $limit = $this->getProduct()->getData($this->_getPositionLimitField());
        if ($limit === null) {
            // use configuration settings
            $limit = $this->_targetRuleData->getMaximumNumberOfProduct($this->getProductListType());
            $this->getProduct()->setData($this->_getPositionLimitField(), $limit);
        }
        return $this->_targetRuleData->getMaxProductsListResult($limit);
    }

    /**
     * Retrieve Position Behavior
     *
     * @return int
     */
    public function getPositionBehavior()
    {
        $behavior = $this->getProduct()->getData($this->_getPositionBehaviorField());
        if ($behavior === null) {
            // use configuration settings
            $behavior = $this->_targetRuleData->getShowProducts($this->getProductListType());
            $this->getProduct()->setData($this->_getPositionBehaviorField(), $behavior);
        }
        return $behavior;
    }

    /**
     * Retrieve array of exclude product ids
     *
     * @return array
     */
    public function getExcludeProductIds()
    {
        if ($this->_excludeProductIds === null) {
            $this->_excludeProductIds = [$this->getProduct()->getEntityId()];
        }
        return $this->_excludeProductIds;
    }

    /**
     * Get link collection with limit parameter
     *
     * @param null|int $limit
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getPreparedTargetLinkCollection($limit = null)
    {
        $linkCollection = null;
        switch ($this->getProductListType()) {
            case \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS:
                $linkCollection = $this->getProduct()->getRelatedProductCollection();
                break;

            case \Magento\TargetRule\Model\Rule::UP_SELLS:
                $linkCollection = $this->getProduct()->getUpSellProductCollection();
                break;

            default:
                throw new LocalizedException(
                    __('The Catalog Product List Type needs to be defined. Verify the type and try again.')
                );
        }

        if ($limit !== null) {
            $this->_addProductAttributesAndPrices($linkCollection);
            $linkCollection->setPageSize($limit);
        }

        $linkCollection->setVisibility(
            $this->_visibility->getVisibleInCatalogIds()
        )->setFlag(
            'do_not_use_category_id',
            true
        );

        $excludeProductIds = $this->getExcludeProductIds();
        if ($excludeProductIds) {
            $linkCollection->addAttributeToFilter('entity_id', ['nin' => $excludeProductIds]);
        }

        return $linkCollection;
    }

    /**
     * Get link collection for related and up-sell
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|null
     */
    protected function _getTargetLinkCollection()
    {
        return $this->_getPreparedTargetLinkCollection($this->_targetRuleData->getMaxProductsListResult());
    }

    /**
     * Retrieve count of related linked products assigned to product
     *
     * @return int
     */
    public function getLinkCollectionCount()
    {
        return count($this->getLinkCollection()->getItems());
    }

    /**
     * Get target rule collection ids
     *
     * @param null|int $limit
     * @return array
     */
    protected function _getTargetRuleProductIds($limit = null)
    {
        $excludeProductIds = $this->getExcludeProductIds();
        if ($this->_items !== null) {
            $excludeProductIds = array_merge(array_keys($this->_items), $excludeProductIds);
        }
        $indexModel = $this->_getTargetRuleIndex()->setType(
            $this->getProductListType()
        )->setLimit(
            $limit
        )->setProduct(
            $this->getProduct()
        )->setExcludeProductIds(
            $excludeProductIds
        );
        if ($limit !== null) {
            $indexModel->setLimit($limit);
        }

        return $indexModel->getProductIds();
    }

    /**
     * Get target rule collection for related and up-sell
     *
     * @return array
     */
    protected function _getTargetRuleProducts()
    {
        $limit = $this->_targetRuleData->getMaxProductsListResult();

        $productIds = $this->_getTargetRuleProductIds($limit);

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
            /** @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
            $collection = $this->_productCollectionFactory->create();
            $collection->addFieldToFilter('entity_id', ['in' => array_keys($productIds)]);
            $this->_addProductAttributesAndPrices($collection);

            $collection->setPageSize(
                $limit
            )->setFlag(
                'do_not_use_category_id',
                true
            )->setVisibility(
                $this->_visibility->getVisibleInCatalogIds()
            );

            foreach ($collection as $item) {
                $items[$item->getEntityId()] = $item;
                $item->setPriority($productIds[$item->getEntityId()]);
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
     * Get ids of all assigned products
     *
     * @return array
     */
    public function getAllIds()
    {
        if ($this->_allProductIds === null) {
            $targetRuleProductIds = $this->_getTargetRuleProductIds();
            $linkProductCollection = $this->_getPreparedTargetLinkCollection();
            $linkProductIds = [];
            foreach ($linkProductCollection as $item) {
                $linkProductIds[] = $item->getEntityId();
            }

            $targetRuleProductIds = $this->rotation->reorder(
                array_diff_key($targetRuleProductIds, array_flip($linkProductIds)),
                $this->_targetRuleData->getRotationMode($this->getProductListType())
            );

            $this->_allProductIds = array_unique(array_merge($linkProductIds, array_keys($targetRuleProductIds)));
        }

        return $this->_allProductIds;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];
        foreach ($this->getItemCollection() as $item) {
            foreach ($item->getIdentities() as $identity) {
                $identities[] = $identity;
            }
        }
        return $identities;
    }

    /**
     * Get all items
     *
     * @return array
     */
    public function getAllItems()
    {
        return $this->getItemCollection();
    }
}
