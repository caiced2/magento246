<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Indexer\TargetRule\Plugin;

use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor as ProductRuleProcessor;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor as RuleProductProcessor;

class Category
{
    /**
     * @var ProductRuleProcessor
     */
    private $productRuleIndexer;

    /**
     * @var RuleProductProcessor
     */
    private $ruleProductIndexer;

    /**
     * @param ProductRuleProcessor $productRuleIndexer
     * @param RuleProductProcessor $ruleProductIndexer
     */
    public function __construct(
        ProductRuleProcessor $productRuleIndexer,
        RuleProductProcessor $ruleProductIndexer
    ) {
        $this->productRuleIndexer = $productRuleIndexer;
        $this->ruleProductIndexer = $ruleProductIndexer;
    }

    /**
     * Invalidate target rule indexer after deleting category
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return \Magento\Catalog\Model\Category
     */
    public function afterDelete(\Magento\Catalog\Model\Category $category)
    {
        $this->invalidateIndexers();
        return $category;
    }

    /**
     * Invalidate target rule indexer after changing category products
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return \Magento\Catalog\Model\Category
     */
    public function afterSave(\Magento\Catalog\Model\Category $category)
    {
        $isChangedProductList = $category->getData('is_changed_product_list');
        if ($isChangedProductList) {
            $this->invalidateIndexers();
        }
        return $category;
    }

    /**
     * Invalidate indexers
     *
     * @return void
     */
    private function invalidateIndexers()
    {
        if (!$this->productRuleIndexer->isIndexerScheduled()) {
            $this->productRuleIndexer->markIndexerAsInvalid();
        }

        if (!$this->ruleProductIndexer->isIndexerScheduled()) {
            $this->ruleProductIndexer->markIndexerAsInvalid();
        }
    }
}
