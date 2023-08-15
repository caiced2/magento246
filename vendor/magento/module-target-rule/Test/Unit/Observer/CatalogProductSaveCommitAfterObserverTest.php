<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor;
use Magento\TargetRule\Observer\CatalogProductSaveCommitAfterObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogProductSaveCommitAfterObserverTest extends TestCase
{
    /**
     * Tested observer
     *
     * @var CatalogProductSaveCommitAfterObserver
     */
    protected $_observer;

    /**
     * Product-Rule processor mock
     *
     * @var Processor|MockObject
     */
    protected $_productRuleProcessorMock;

    protected function setUp(): void
    {
        $this->_productRuleProcessorMock = $this->createMock(
            Processor::class
        );

        $this->_observer = (new ObjectManager($this))->getObject(
            CatalogProductSaveCommitAfterObserver::class,
            [
                'productRuleIndexerProcessor' => $this->_productRuleProcessorMock,
            ]
        );
    }

    public function testCatalogProductSaveCommitAfter()
    {
        $productMock = $this->createPartialMock(
            Product::class,
            ['getId', '__sleep', '__wakeup']
        );
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getProduct'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $productMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->_productRuleProcessorMock->expects($this->once())
            ->method('reindexRow')
            ->with(1);

        $this->_observer->execute($observerMock);
    }
}
