<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Indexer\TargetRule\Product;

class Rule implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * Product-Rule indexer row action
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row
     */
    protected $_productRuleIndexerRow;

    /**
     * Product-Rule indexer rows action
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Rows
     */
    protected $_productRuleIndexerRows;

    /**
     * Product-Rule indexer full reindex action
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full
     */
    protected $_productRuleIndexerFull;

    /**
     * Rule-Product indexer processor
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor
     */
    protected $_ruleProductProcessor;

    /**
     * Product-Rule indexer processor
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor
     */
    protected $_productRuleProcessor;

    /**
     * Product-Rule indexer clean action
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean
     */
    protected $_productRuleIndexerClean;

    /**
     * Product-Rule indexer clean products relations action
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct
     */
    protected $_productRuleIndexerCleanDeleteProduct;

    /**
     * Construct
     *
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row $productRuleIndexerRow
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Rows $productRuleIndexerRows
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full $productRuleIndexerFull
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductProcessor
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleProcessor
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean $productRuleIndexerClean
     * @param Rule\Action\CleanDeleteProduct $productRuleIndexerCleanDeleteProduct
     */
    public function __construct(
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row $productRuleIndexerRow,
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Rows $productRuleIndexerRows,
        \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full $productRuleIndexerFull,
        \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductProcessor,
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleProcessor,
        \Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean $productRuleIndexerClean,
        Rule\Action\CleanDeleteProduct $productRuleIndexerCleanDeleteProduct
    ) {
        $this->_productRuleIndexerRow = $productRuleIndexerRow;
        $this->_productRuleIndexerRows = $productRuleIndexerRows;
        $this->_productRuleIndexerFull = $productRuleIndexerFull;
        $this->_ruleProductProcessor = $ruleProductProcessor;
        $this->_productRuleProcessor = $productRuleProcessor;
        $this->_productRuleIndexerClean = $productRuleIndexerClean;
        $this->_productRuleIndexerCleanDeleteProduct = $productRuleIndexerCleanDeleteProduct;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $productIds
     *
     * @return void
     */
    public function execute($productIds)
    {
        $this->_productRuleIndexerRows->execute($productIds);
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        if (!$this->_ruleProductProcessor->isFullReindexPassed()) {
            $this->_productRuleIndexerFull->execute();
            $this->_ruleProductProcessor->setFullReindexPassed();
        }
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $productIds
     *
     * @return void
     */
    public function executeList(array $productIds)
    {
        $this->_productRuleIndexerRows->execute($productIds);
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $productId
     *
     * @return void
     */
    public function executeRow($productId)
    {
        $this->_productRuleIndexerRow->execute($productId);
    }

    /**
     * Execute clean index
     *
     * @return void
     */
    public function cleanByCron()
    {
        $this->_productRuleIndexerClean->execute();
    }

    /**
     * Clean deleted products from index
     *
     * @param int $productId
     *
     * @return void
     */
    public function cleanAfterProductDelete($productId)
    {
        $this->_productRuleIndexerCleanDeleteProduct->execute($productId);
    }
}
