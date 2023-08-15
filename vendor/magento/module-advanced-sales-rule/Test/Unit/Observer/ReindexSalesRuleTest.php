<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Observer;

use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor;
use Magento\AdvancedSalesRule\Observer\ReindexSalesRule;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReindexSalesRuleTest extends TestCase
{
    /**
     * @var ReindexSalesRule
     */
    protected $observer;

    /**
     * @var Processor|MockObject
     */
    protected $indexProcessorMock;

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

        $this->indexProcessorMock = $this->getMockBuilder(
            Processor::class
        )->disableOriginalConstructor()
            ->getMock();

        $this->observer = $this->objectManager->getObject(
            ReindexSalesRule::class,
            [
                'indexerProcessor' => $this->indexProcessorMock,
            ]
        );
    }

    /**
     * test Execute
     */
    public function testExecute()
    {
        $ids = [1, 2, 3];

        /** @var Observer $observerData */
        $observerData = $this->objectManager->getObject(
            Observer::class,
            [
                'data' => [
                    'entity_ids' => $ids,
                ],
            ]
        );

        $this->indexProcessorMock->expects($this->once())
            ->method('reindexList')
            ->with($ids);

        $this->observer->execute($observerData);
    }
}
