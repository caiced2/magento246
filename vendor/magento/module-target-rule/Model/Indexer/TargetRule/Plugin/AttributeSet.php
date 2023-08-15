<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Indexer\TargetRule\Plugin;

use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor as ProductRuleProcessor;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor as RuleProductProcessor;

class AttributeSet
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
     * Invalidate target rule indexer after deleting attribute set
     *
     * @param \Magento\Eav\Model\Entity\Attribute\Set $attributeSet
     *
     * @return \Magento\Eav\Model\Entity\Attribute\Set
     */
    public function afterDelete(\Magento\Eav\Model\Entity\Attribute\Set $attributeSet)
    {
        $this->invalidateIndexers();
        return $attributeSet;
    }

    /**
     * Invalidate indexers
     *
     * @return void
     */
    private function invalidateIndexers()
    {
        $this->productRuleIndexer->markIndexerAsInvalid();
        $this->ruleProductIndexer->markIndexerAsInvalid();
    }
}
