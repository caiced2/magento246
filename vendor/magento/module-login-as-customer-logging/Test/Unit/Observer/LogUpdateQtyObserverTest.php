<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Logging\Model\ResourceModel\Event;
use \Magento\Logging\Model\Event as LogEvent;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Magento\LoginAsCustomerLogging\Observer\LogUpdateQtyObserver;
use Magento\LoginAsCustomerLogging\Model\LogValidation;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogUpdateQtyObserverTest extends TestCase
{
    /**
     * @var LogUpdateQtyObserver
     */
    private LogUpdateQtyObserver $logUpdateQtyObserver;

    /**
     * @var GetEventForLogging|MockObject
     */
    private $getEventForLogging;

    /**
     * @var LogValidation|MockObject
     */
    private $logValidation;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var Item|MockObject
     */
    private $item;

    /**
     * @var GetLoggedAsCustomerAdminIdInterface|MockObject
     */
    private $getLoggedAsCustomerAdminId;

    /**
     * @var LogEvent|MockObject
     */
    private $logEvent;

    protected function setUp(): void
    {
        $this->logValidation = $this->createMock(LogValidation::class);
        $this->getEventForLogging = $this->createMock(GetEventForLogging::class);
        $this->event = $this->createMock(Event::class);
        $this->observer = $this->getMockBuilder(Observer::class)
            ->addMethods(['getItem'])
            ->onlyMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->getLoggedAsCustomerAdminId = $this->createMock(GetLoggedAsCustomerAdminIdInterface::class);
        $this->logEvent = $this->getMockBuilder(LogEvent::class)
            ->addMethods(['setAction', 'setInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->item = $this->getMockBuilder(Item::class)
            ->addMethods(['getCustomer', 'getEmail'])
            ->onlyMethods(['getId', 'getOrigData', 'getData', 'getQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logUpdateQtyObserver = new LogUpdateQtyObserver(
            $this->getEventForLogging,
            $this->event,
            $this->getLoggedAsCustomerAdminId,
            $this->logValidation
        );
    }

    public function testExecuteButShouldNotBeLogged()
    {
        $this->logValidation->expects($this->once())->method('shouldBeLogged')->willReturn(false);
        $this->observer->expects($this->never())->method('getEvent');
        $this->logUpdateQtyObserver->execute($this->observer);
    }

    public function testExecuteButShouldNotBeLoggedWhileAddingProductToCart()
    {
        $this->logValidation->expects($this->once())->method('shouldBeLogged')->willReturn(true);
        $this->observer->expects($this->once())->method('getEvent')->willReturnSelf();
        $this->observer->expects($this->once())->method('getItem')->willReturn($this->item);

        $this->item->expects($this->once())
            ->method('getOrigData')
            ->with('qty')
            ->willReturn(0);

        $this->getEventForLogging->expects($this->never())->method('execute');

        $this->logUpdateQtyObserver->execute($this->observer);
    }

    public function testExecute()
    {
        $this->logValidation->expects($this->once())->method('shouldBeLogged')->willReturn(true);
        $this->observer->expects($this->once())->method('getEvent')->willReturnSelf();
        $this->observer->expects($this->once())->method('getItem')->willReturn($this->item);

        $this->item->expects($this->exactly(3))
            ->method('getOrigData')
            ->with('qty')
            ->willReturn(2);
        $this->item->expects($this->exactly(4))
            ->method('getData')
            ->withConsecutive(
                ['qty'],
                ['quote_id'],
                ['sku'],
                ['qty']
            )
            ->willReturnOnConsecutiveCalls(
                5,
                1,
                'sku',
                5
            );

        $this->getLoggedAsCustomerAdminId->expects($this->once())
            ->method('execute')
            ->willReturn(3);

        $this->getEventForLogging->expects($this->once())
            ->method('execute')
            ->with(3)
            ->willReturn($this->logEvent);

        $this->logEvent->expects($this->once())
            ->method('setAction')
            ->with('update_qty');

        $this->item->expects($this->exactly(2))
            ->method('getQuote')
            ->willReturnSelf();
        $this->item->expects($this->exactly(2))
            ->method('getCustomer')
            ->willReturnSelf();
        $this->item->expects($this->exactly(1))
            ->method('getId')
            ->willReturn(1);
        $this->item->expects($this->exactly(1))
            ->method('getEmail')
            ->willReturn('test@example.com');
        $this->logEvent->expects($this->once())
            ->method('setInfo');
        $this->event->expects($this->once())
            ->method('save')
            ->with($this->logEvent);

        $this->logUpdateQtyObserver->execute($this->observer);
    }
}
