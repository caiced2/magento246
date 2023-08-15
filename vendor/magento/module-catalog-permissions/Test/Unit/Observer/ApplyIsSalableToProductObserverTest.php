<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Observer;

use Magento\CatalogPermissions\Observer\ApplyIsSalableToProductObserver;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\CatalogPermissions\Observer\ApplyIsSalableToProductObserver
 */
class ApplyIsSalableToProductObserverTest extends TestCase
{
    /**
     * @var ApplyIsSalableToProductObserver
     */
    protected $observer;

    /**
     * @var Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->eventObserverMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new ApplyIsSalableToProductObserver();
    }

    /**
     * @return void
     */
    public function testApplyIsSalableToProduct()
    {
        $salableMock = $this->getMockBuilder(DataObject::class)
            ->setMethods(['setIsSalable'])
            ->disableOriginalConstructor()
            ->getMock();

        $salableMock
            ->expects($this->once())
            ->method('setIsSalable')
            ->with(false);

        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(
                new DataObject(
                    [
                        'salable' => $salableMock,
                        'product' => new DataObject(['disable_add_to_cart' => true])
                    ]
                )
            );

        $this->observer->execute($this->eventObserverMock);
    }
}
