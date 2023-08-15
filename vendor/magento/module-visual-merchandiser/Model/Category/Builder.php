<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Category;

use \Magento\Framework\DB\Select;

/**
 * Class Builder
 *
 * @package Magento\VisualMerchandiser\Model\Category
 *
 * @api
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Builder
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\VisualMerchandiser\Model\Sorting
     */
    protected $sorting;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    private $rules;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\VisualMerchandiser\Model\Sorting $sorting
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\VisualMerchandiser\Model\Rules $rules,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\VisualMerchandiser\Model\Sorting $sorting
    ) {
        $this->resource = $resource;
        $this->rules = $rules;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->sorting = $sorting;
    }

    /**
     * @return string
     */
    protected function getRuleTable()
    {
        return $this->resource->getTableName('visual_merchandiser_rule');
    }

    /**
     * Rebuild category
     *
     * @param \Magento\Catalog\Model\Category $category
     *
     * @return void
     */
    public function rebuildCategory(\Magento\Catalog\Model\Category $category)
    {
        $collection = $this->productCollectionFactory->create()->setStoreId(
            $this->storeManager->getStore()->getId()
        );

        // Apply 'smart rules', this works on the whole product collection
        $this->rules->applyAllRules($category, $collection);

        // Limit the product set to only the products needed
        $existingProducts = $category->getPostedProducts();
        if ($existingProducts === null) {
            $existingProducts = $category->getProductsPosition();
        }
        asort($existingProducts, SORT_NUMERIC);
        $existingProducts = array_keys($existingProducts);

        $collection->getSelect()->reset(Select::WHERE);
        $collection->getSelect()->reset(Select::HAVING);
        $collection->getSelect()->reset(Select::SQL_HAVING);
        $collection->addAttributeToFilter('entity_id', ['in' => $existingProducts]);
        if (count($existingProducts) > 0) {
            $collection->getSelect()->reset(Select::ORDER);
            $collection->getSelect()
                ->order(new \Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $existingProducts) . ')'));
        }

        // Apply 'sort'
        $sortedCollection = $this->sorting->applySorting($category, $collection);

        $positions = [];
        $idx = 0;
        if (count($existingProducts) > 0) {
            foreach ($sortedCollection as $item) {
                /* @var $item \Magento\Catalog\Api\Data\ProductInterface */
                $positions[$item->getId()] = $idx;
                $idx++;
            }
        }
        $category->setPostedProducts($positions);
    }
}
