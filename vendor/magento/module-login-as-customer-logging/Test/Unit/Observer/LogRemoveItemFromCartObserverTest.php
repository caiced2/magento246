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
use Magento\LoginAsCustomerLogging\Observer\LogRemoveItemFromCartObserver;
use Magento\LoginAsCustomerLogging\Model\LogValidation;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogRemoveItemFromCartObserverTest extends TestCase
{
    /**
     * @var LogRemoveItemFromCartObserver
     */
    private LogRemoveItemFromCartObserver $logRemoveItemFromCartObserver;

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
            ->addMethods(['getQuoteItem'])
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

        $this->logRemoveItemFromCartObserver = new LogRemoveItemFromCartObserver(
            $this->getEventForLogging,
            $this->event,
            $this->logValidation,
            $this->getLoggedAsCustomerAdminId
        );
    }

    public function testExecuteButShouldNotBeLogged()
    {
        $this->logValidation->expects($this->once())->method('shouldBeLogged')->willReturn(false);
        $this->observer->expects($this->never())->method('getEvent');
        $this->logRemoveItemFromCartObserver->execute($this->observer);
    }

    public function testExecute()
    {
        $this->logValidation->expects($this->once())->method('shouldBeLogged')->willReturn(true);
        $this->observer->expects($this->once())->method('getEvent')->willReturnSelf();
        $this->observer->expects($this->once())->method('getQuoteItem')->willReturn($this->item);

        $this->item->expects($this->exactly(2))
            ->method('getData')
            ->withConsecutive(
                ['quote_id'],
                ['sku'],
            )
            ->willReturnOnConsecutiveCalls(
                5,
                'sku',
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
            ->with('remove_cart_item');

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

        $this->logRemoveItemFromCartObserver->execute($this->observer);
    }
}
