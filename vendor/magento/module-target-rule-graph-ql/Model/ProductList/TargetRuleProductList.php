<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRuleGraphQl\Model\ProductList;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\TargetRule\Helper\Data;
use Magento\TargetRule\Model\IndexFactory;
use Magento\TargetRule\Model\Rotation;
use Magento\TargetRule\Model\ResourceModel\Index as ResourceIndex;

/**
 * Find linked products by target rule
 */
class TargetRuleProductList
{
    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var Config
     */
    private $catalogConfig;

    /**
     * @var Data
     */
    private $targetRuleHelper;

    /**
     * @var Rotation
     */
    private $rotation;

    /**
     * @var IndexFactory
     */
    private $ruleIndexFactory;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ResourceIndex
     */
    private $resourceIndex;

    /**
     * @param Visibility $visibility
     * @param Config $catalogConfig
     * @param Data $targetRuleHelper
     * @param Rotation $rotation
     * @param IndexFactory $ruleIndexFactory
     * @param CollectionFactory $productCollectionFactory
     * @param ResourceIndex $resourceIndex
     */
    public function __construct(
        Visibility $visibility,
        Config $catalogConfig,
        Data $targetRuleHelper,
        Rotation $rotation,
        IndexFactory $ruleIndexFactory,
        CollectionFactory $productCollectionFactory,
        ResourceIndex $resourceIndex
    ) {
        $this->visibility = $visibility;
        $this->catalogConfig = $catalogConfig;
        $this->targetRuleHelper = $targetRuleHelper;
        $this->rotation = $rotation;
        $this->ruleIndexFactory = $ruleIndexFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resourceIndex = $resourceIndex;
    }

    /**
     * Add needed attributes into product collection.
     *
     * @param Collection $collection
     * @return Collection
     */
    private function addProductAttributesAndPrices(
        Collection $collection
    ): Collection {
        return $collection
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addUrlRewrite();
    }

    /**
     * Get target rule collection for related, up-sell and cross-sell.
     *
     * @param ContextInterface $context
     * @param ProductInterface $product
     * @param int $listType
     * @return ProductInterface[]
     */
    public function getTargetRuleProducts(ContextInterface $context, ProductInterface $product, int $listType): array
    {
        $limit = $this->targetRuleHelper->getMaxProductsListResult();
        $productIds = $this->getTargetRuleProductIds($context, $product, $listType, $limit);
        $productIds = $this->rotation->reorder(
            $productIds,
            $this->targetRuleHelper->getRotationMode($listType),
            $limit
        );

        $items = [];
        if ($productIds) {
            /** @var $collection Collection */
            $collection = $this->productCollectionFactory->create();
            $collection->addFieldToFilter('entity_id', ['in' => array_keys($productIds)]);
            $this->addProductAttributesAndPrices($collection);

            $collection->setPageSize(
                $limit
            )->setFlag(
                'do_not_use_category_id',
                true
            )->setVisibility(
                $this->visibility->getVisibleInCatalogIds()
            );

            foreach ($collection->getItems() as $item) {
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
     * Get target rule collection ids.
     *
     * @param ContextInterface $context
     * @param ProductInterface $product
     * @param int $listType
     * @param int $limit
     * @return array
     */
    private function getTargetRuleProductIds(
        ContextInterface $context,
        ProductInterface $product,
        int $listType,
        int $limit
    ): array {
        $excludeProductIds = $this->getExcludeProductIds($product);
        $indexModel = $this->ruleIndexFactory->create();
        $indexModel->setType(
            $listType
        )->setLimit(
            $limit
        )->setProduct(
            $product
        )->setExcludeProductIds(
            $excludeProductIds
        );

        if (($store = $context->getExtensionAttributes()->getStore())) {
            $websiteId = $store->getWebsite()->getId();
        } else {
            $websiteId = null;
        }
        return $this->resourceIndex->getProductIds($indexModel, $context->getUserId(), $websiteId);
    }

    /**
     * Retrieve array of exclude product ids.
     *
     * @param ProductInterface $product
     * @return int[]
     */
    private function getExcludeProductIds(ProductInterface $product): array
    {
        return [(int)$product->getId()];
    }
}
