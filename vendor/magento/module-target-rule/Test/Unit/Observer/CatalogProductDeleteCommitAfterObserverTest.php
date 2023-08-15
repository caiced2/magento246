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
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule;
use Magento\TargetRule\Observer\CatalogProductDeleteCommitAfterObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogProductDeleteCommitAfterObserverTest extends TestCase
{
    /**
     * Tested observer
     *
     * @var CatalogProductDeleteCommitAfterObserver
     */
    protected $_observer;

    /**
     * Product-Rule indexer mock
     *
     * @var Rule|MockObject
     */
    protected $_productRuleIndexer;

    protected function setUp(): void
    {
        $this->_productRuleIndexer = $this->createMock(
            Rule::class
        );

        $this->_observer = (new ObjectManager($this))->getObject(
            CatalogProductDeleteCommitAfterObserver::class,
            [
                'productRuleIndexer' => $this->_productRuleIndexer,
            ]
        );
    }

    public function testCatalogProductDeleteCommitAfter()
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

        $this->_productRuleIndexer->expects($this->once())
            ->method('cleanAfterProductDelete')
            ->with(1);

        $this->_observer->execute($observerMock);
    }
}
