<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Indexer\TargetRule\Rule;

class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row
     */
    protected $_ruleProductIndexerRow;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows
     */
    protected $_ruleProductIndexerRows;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full
     */
    protected $_ruleProductIndexerFull;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor
     */
    protected $_productRuleProcessor;

    /**
     * @var Product\Processor
     */
    protected $_ruleProductProcessor;

    /**
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row $ruleProductIndexerRow
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows $ruleProductIndexerRows
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full $ruleProductIndexerFull
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleProcessor
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductProcessor
     */
    public function __construct(
        \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row $ruleProductIndexerRow,
        \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows $ruleProductIndexerRows,
        \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full $ruleProductIndexerFull,
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleProcessor,
        \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductProcessor
    ) {
        $this->_ruleProductIndexerRow = $ruleProductIndexerRow;
        $this->_ruleProductIndexerRows = $ruleProductIndexerRows;
        $this->_ruleProductIndexerFull = $ruleProductIndexerFull;
        $this->_productRuleProcessor = $productRuleProcessor;
        $this->_ruleProductProcessor = $ruleProductProcessor;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ruleId
     *
     * @return void
     */
    public function execute($ruleId)
    {
        $this->_ruleProductIndexerRows->execute($ruleId);
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        if (!$this->_productRuleProcessor->isFullReindexPassed()) {
            $this->_ruleProductIndexerFull->execute();
            $this->_productRuleProcessor->setFullReindexPassed();
        }
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ruleIds
     *
     * @return void
     */
    public function executeList(array $ruleIds)
    {
        $this->_ruleProductIndexerRows->execute($ruleIds);
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $ruleId
     *
     * @return void
     */
    public function executeRow($ruleId)
    {
        $this->_ruleProductIndexerRow->execute($ruleId);
    }
}
