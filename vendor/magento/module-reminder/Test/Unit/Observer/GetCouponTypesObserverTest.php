<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Mail\Transport;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reminder\Observer\GetCouponTypesObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetCouponTypesObserverTest extends TestCase
{
    /**
     * @var GetCouponTypesObserver
     */
    private $model;

    /**
     * @var Observer|MockObject
     */
    private $eventObserver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $helper = new ObjectManager($this);

        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->setMethods(['getCollection', 'getRule', 'getForm', 'getEvent'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $helper->getObject(
            GetCouponTypesObserver::class
        );
    }

    /**
     * @return void
     */
    public function testGetCouponTypes()
    {
        $transportMock = $this->getMockBuilder(Transport::class)
            ->setMethods(['setIsCouponTypeAutoVisible'])
            ->disableOriginalConstructor()
            ->getMock();
        $transportMock->expects($this->once())->method('setIsCouponTypeAutoVisible')->with(true);

        $eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getTransport'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getTransport')->willReturn($transportMock);

        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $this->model->execute($this->eventObserver);
    }
}
