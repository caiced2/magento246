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

class CustomerSegment
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
     * Invalidate target rule indexer after deleting customer segment
     *
     * @param \Magento\CustomerSegment\Model\Segment $customerSegment
     * @return \Magento\CustomerSegment\Model\Segment
     */
    public function afterDelete(\Magento\CustomerSegment\Model\Segment $customerSegment)
    {
        $this->invalidateIndexers();
        return $customerSegment;
    }

    /**
     * Invalidate target rule indexer after changing customer segment
     *
     * @param \Magento\CustomerSegment\Model\Segment $customerSegment
     * @return \Magento\CustomerSegment\Model\Segment
     */
    public function afterSave(\Magento\CustomerSegment\Model\Segment $customerSegment)
    {
        if (!$customerSegment->isObjectNew()) {
            $this->invalidateIndexers();
        }
        return $customerSegment;
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
