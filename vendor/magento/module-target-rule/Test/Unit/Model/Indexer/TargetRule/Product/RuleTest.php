<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Product;

use Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean;
use Magento\TargetRule\Model\Indexer\TargetRule\Action\Full;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Rows;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RuleTest extends TestCase
{
    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule
     */
    protected $_ruleIndexer;

    /**
     * @var Processor|MockObject
     */
    protected $_ruleProductProcessor;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor|MockObject
     */
    protected $_productRuleProcessor;

    /**
     * @var Full|MockObject
     */
    protected $_actionFull;

    /**
     * @var Clean|MockObject
     */
    protected $_actionClean;

    /**
     * @var Rule\Action\CleanDeleteProduct|MockObject
     */
    protected $_actionCleanDeleteProduct;

    protected function setUp(): void
    {
        $this->_ruleProductProcessor = $this->createMock(
            Processor::class
        );
        $this->_productRuleProcessor = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor::class
        );
        $this->_actionFull = $this->createMock(Full::class);
        $actionRow = $this->createMock(Row::class);
        $actionRows = $this->createMock(Rows::class);
        $this->_actionClean = $this->createMock(Clean::class);
        $this->_actionCleanDeleteProduct = $this->createMock(
            CleanDeleteProduct::class
        );
        $this->_ruleIndexer = new Rule(
            $actionRow,
            $actionRows,
            $this->_actionFull,
            $this->_ruleProductProcessor,
            $this->_productRuleProcessor,
            $this->_actionClean,
            $this->_actionCleanDeleteProduct
        );
    }

    public function testFullReindexIfNotExecutedRelatedIndexer()
    {
        $this->_actionFull->expects($this->once())
            ->method('execute');
        $this->_ruleProductProcessor->expects($this->once())
            ->method('isFullReindexPassed')
            ->willReturn(false);
        $this->_ruleProductProcessor->expects($this->once())
            ->method('setFullReindexPassed');
        $this->_ruleIndexer->executeFull();
    }

    public function testFullReindexIfRelatedIndexerPassed()
    {
        $this->_actionFull->expects($this->never())
            ->method('execute');
        $this->_ruleProductProcessor->expects($this->once())
            ->method('isFullReindexPassed')
            ->willReturn(true);
        $this->_ruleProductProcessor->expects($this->never())
            ->method('setFullReindexPassed');
        $this->_ruleIndexer->executeFull();
    }
}
