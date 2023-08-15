<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Indexer\TargetRule\Plugin;

use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor as ProductRuleProcessor;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor as RuleProductProcessor;

class StoreGroup
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
     * Before save handler
     *
     * @param \Magento\Store\Model\ResourceModel\Group $subject
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        \Magento\Store\Model\ResourceModel\Group $subject,
        \Magento\Framework\Model\AbstractModel $object
    ) {
        if (!$object->getId() || $object->dataHasChangedFor('root_category_id')) {
            $this->invalidateIndexers();
        }
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
