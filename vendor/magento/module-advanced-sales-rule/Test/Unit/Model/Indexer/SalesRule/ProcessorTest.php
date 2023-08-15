<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Indexer\SalesRule;

use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Indexer\Model\Indexer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
    /**
     * @var Processor
     */
    protected $model;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistry;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $className = IndexerRegistry::class;
        $this->indexerRegistry = $this->createMock($className);

        $this->model = $this->objectManager->getObject(
            Processor::class,
            [
                'indexerRegistry' => $this->indexerRegistry,
            ]
        );
    }

    /**
     * test GetIndexer
     */
    public function testGetIndexer()
    {
        $className = Indexer::class;
        $indexer = $this->createMock($className);

        $this->indexerRegistry->expects($this->any())
            ->method('get')
            ->willReturn($indexer);
        $this->assertSame($indexer, $this->model->getIndexer());
    }
}
