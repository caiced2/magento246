<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Indexer\TargetRule\Plugin;

use Magento\ImportExport\Model\Import as ImportModel;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor as ProductRuleProcessor;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor as RuleProductProcessor;

class Import
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
     * Invalidate target rule indexer
     *
     * @param ImportModel $subject
     * @param bool $result
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterImportSource(ImportModel $subject, $result)
    {
        $this->invalidateIndexers();
        return $result;
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
