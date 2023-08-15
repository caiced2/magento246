<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Test\Unit\Model;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor;
use Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor;
use Magento\CatalogRuleStaging\Model\CatalogRuleApplier;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogRuleApplierTest extends TestCase
{
    /**
     * @var IndexerRegistry|MockObject
     */
    private $indexerRegistryMock;

    /**
     * @var RuleProductProcessor|MockObject
     */
    private $ruleProductProcessorMock;

    /**
     * @var CatalogRuleApplier
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->ruleProductProcessorMock = $this->getMockBuilder(RuleProductProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->indexerRegistryMock = $this->getMockBuilder(IndexerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new CatalogRuleApplier(
            $this->ruleProductProcessorMock,
            $this->indexerRegistryMock
        );
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $entityIds = [1];
        $indexerMock = $this->getMockBuilder(IndexerInterface::class)
            ->getMockForAbstractClass();

        $this->ruleProductProcessorMock->expects($this->atLeastOnce())
            ->method('markIndexerAsInvalid')
            ->willReturnSelf();
        $this->indexerRegistryMock
            ->method('get')
            ->withConsecutive([ProductRuleProcessor::INDEXER_ID], [Processor::INDEXER_ID])
            ->willReturnOnConsecutiveCalls($indexerMock, $indexerMock);
        $indexerMock->expects($this->any())->method('invalidate')->willReturnSelf();

        $this->model->execute($entityIds);
    }

    /**
     * @return void
     */
    public function testExecuteWithNoEntities(): void
    {
        $result = $this->model->execute([]);
        $this->assertNull($result);
    }
}
