<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Model;

use Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor;
use Magento\Staging\Model\StagingApplierInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Class CatalogRuleApplier
 */
class CatalogRuleApplier implements StagingApplierInterface
{
    /**
     * @var RuleProductProcessor
     */
    private $ruleProductProcessor;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * CatalogRuleApplier constructor.
     *
     * @param RuleProductProcessor $ruleProductProcessor
     * @param IndexerRegistry|null $indexerRegistry
     */
    public function __construct(
        RuleProductProcessor $ruleProductProcessor,
        IndexerRegistry $indexerRegistry = null
    ) {
        $this->ruleProductProcessor = $ruleProductProcessor;
        $this->indexerRegistry = $indexerRegistry
            ?: ObjectManager::getInstance()->get(IndexerRegistry::class);
    }

    /**
     * @param array $entityIds
     * @return void
     */
    public function execute(array $entityIds)
    {
        if (!empty($entityIds)) {
            $this->ruleProductProcessor->markIndexerAsInvalid();
            $this->indexerRegistry->get(\Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor::INDEXER_ID)
                ->invalidate();
            $this->indexerRegistry->get(\Magento\Catalog\Model\Indexer\Product\Price\Processor::INDEXER_ID)
                ->invalidate();
        }
    }
}
