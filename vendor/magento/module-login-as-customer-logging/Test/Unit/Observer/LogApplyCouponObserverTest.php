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
use Magento\LoginAsCustomerLogging\Observer\LogApplyCoupon;
use Magento\LoginAsCustomerLogging\Model\LogValidation;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogApplyCouponObserverTest extends TestCase
{
    /**
     * @var LogApplyCoupon
     */
    private LogApplyCoupon $logApplyCoupon;

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
     * @var Quote|MockObject
     */
    private $quote;

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
            ->addMethods(['getQuote'])
            ->onlyMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->getLoggedAsCustomerAdminId = $this->createMock(GetLoggedAsCustomerAdminIdInterface::class);
        $this->logEvent = $this->getMockBuilder(LogEvent::class)
            ->addMethods(['setAction', 'setInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getEmail'])
            ->onlyMethods(['getId', 'getOrigData', 'getData', 'getCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logApplyCoupon = new LogApplyCoupon(
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
        $this->logApplyCoupon->execute($this->observer);
    }

    public function testExecuteButShouldNotBeLoggedWhileAddingProductToCart()
    {
        $this->logValidation->expects($this->once())->method('shouldBeLogged')->willReturn(true);
        $this->observer->expects($this->once())->method('getEvent')->willReturnSelf();
        $this->observer->expects($this->once())->method('getQuote')->willReturn($this->quote);

        $this->quote->expects($this->once())
            ->method('getOrigData')
            ->with('coupon_code')
            ->willReturn('test_coupon');

        $this->getEventForLogging->expects($this->never())->method('execute');

        $this->logApplyCoupon->execute($this->observer);
    }

    public function testExecute()
    {
        $this->logValidation->expects($this->once())->method('shouldBeLogged')->willReturn(true);
        $this->observer->expects($this->once())->method('getEvent')->willReturnSelf();
        $this->observer->expects($this->once())->method('getQuote')->willReturn($this->quote);

        $this->quote->expects($this->exactly(2))
            ->method('getOrigData')
            ->with('coupon_code')
            ->willReturn('');
        $this->quote->expects($this->exactly(2))
            ->method('getData')
            ->withConsecutive(
                ['coupon_code'],
                ['coupon_code'],
            )
            ->willReturnOnConsecutiveCalls(
                'qwe',
                'qwe',
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
            ->with('apply_coupon_code');

        $this->quote->expects($this->exactly(2))
            ->method('getCustomer')
            ->willReturnSelf();
        $this->quote->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);
        $this->quote->expects($this->exactly(1))
            ->method('getEmail')
            ->willReturn('test@example.com');
        $this->logEvent->expects($this->once())
            ->method('setInfo');
        $this->event->expects($this->once())
            ->method('save')
            ->with($this->logEvent);

        $this->logApplyCoupon->execute($this->observer);
    }
}
