<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Observer\Backend;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\Store;
use Magento\VersionsCms\Observer\Backend\CleanStoreFootprints;
use Magento\VersionsCms\Observer\Backend\DeleteStoreObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteStoreObserverTest extends TestCase
{
    /**
     * @var CleanStoreFootprints|MockObject
     */
    protected $cleanStoreFootprintsMock;

    /**
     * @var Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var DeleteStoreObserver
     */
    protected $observer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->cleanStoreFootprintsMock = $this->createMock(
            CleanStoreFootprints::class
        );
        $this->eventObserverMock = $this->createMock(Observer::class);

        $this->observer = $this->objectManagerHelper->getObject(
            DeleteStoreObserver::class,
            [
                'cleanStoreFootprints' => $this->cleanStoreFootprintsMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testDeleteStore()
    {
        $storeId = 2;

        /** @var Store|MockObject $storeMock */
        $storeMock = $this->createPartialMock(Store::class, ['getId']);
        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        /** @var Event|MockObject $eventMock */
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $this->cleanStoreFootprintsMock->expects($this->once())->method('clean')->with($storeId);

        $this->observer->execute($this->eventObserverMock);
    }
}
