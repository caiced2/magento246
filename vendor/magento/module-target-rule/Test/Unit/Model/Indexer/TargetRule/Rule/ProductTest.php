<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Rule;

use Magento\TargetRule\Model\Indexer\TargetRule\Action\Full;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    /**
     * @var Product
     */
    protected $_productIndexer;

    /**
     * @var Processor|MockObject
     */
    protected $_productRuleProcessor;

    /**
     * @var Full|MockObject
     */
    protected $_ruleProductIndexerFull;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor|MockObject
     */
    protected $_ruleProductProcessor;

    protected function setUp(): void
    {
        $this->_ruleProductProcessor = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor::class
        );
        $this->_productRuleProcessor = $this->createMock(
            Processor::class
        );
        $this->_ruleProductIndexerFull = $this->createMock(
            Full::class
        );
        $ruleProductIndexerRows = $this->createMock(
            Rows::class
        );
        $ruleProductIndexerRow = $this->createMock(
            Row::class
        );
        $this->_productIndexer = new Product(
            $ruleProductIndexerRow,
            $ruleProductIndexerRows,
            $this->_ruleProductIndexerFull,
            $this->_productRuleProcessor,
            $this->_ruleProductProcessor
        );
    }

    public function testFullReindexIfNotExecutedRelatedIndexer()
    {
        $this->_ruleProductIndexerFull->expects($this->once())
            ->method('execute');
        $this->_productRuleProcessor->expects($this->once())
            ->method('isFullReindexPassed')
            ->willReturn(false);
        $this->_productRuleProcessor->expects($this->once())
            ->method('setFullReindexPassed');
        $this->_productIndexer->executeFull();
    }

    public function testFullReindexIfRelatedIndexerPassed()
    {
        $this->_ruleProductIndexerFull->expects($this->never())
            ->method('execute');
        $this->_productRuleProcessor->expects($this->once())
            ->method('isFullReindexPassed')
            ->willReturn(true);
        $this->_productRuleProcessor->expects($this->never())
            ->method('setFullReindexPassed');
        $this->_productIndexer->executeFull();
    }
}
