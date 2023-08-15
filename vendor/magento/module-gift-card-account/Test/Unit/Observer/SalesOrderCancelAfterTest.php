<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Observer\ReturnFundsToStoreCredit;
use Magento\GiftCardAccount\Observer\RevertGiftCardAccountBalance;
use Magento\GiftCardAccount\Observer\SalesOrderCancelAfter;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesOrderCancelAfterTest extends TestCase
{
    /** @var SalesOrderCancelAfter */
    private $model;

    /**
     * @var ReturnFundsToStoreCredit|MockObject
     */
    private $returnFuncsToStoreCreditMock;

    /**
     * @var RevertGiftCardAccountBalance|MockObject
     */
    private $revertGiftCardAccountBalanceMock;

    /**
     * @var Observer|MockObject
     */
    private $observerMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->returnFuncsToStoreCreditMock = $this->getMockBuilder(ReturnFundsToStoreCredit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->revertGiftCardAccountBalanceMock = $this->getMockBuilder(RevertGiftCardAccountBalance::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $objectManagerHelper->getObject(
            SalesOrderCancelAfter::class,
            [
                'returnFundsToStoreCredit' => $this->returnFuncsToStoreCreditMock,
                'revertGiftCardAccountBalance' => $this->revertGiftCardAccountBalanceMock,
            ]
        );
    }

    /**
     * @param int|null $customerId
     * @dataProvider executeDataProvider
     */
    public function testExecute($customerId)
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder'])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);
        $this->observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);
        $this->returnFuncsToStoreCreditMock->expects($this->exactly($customerId ? 1 : 0))
            ->method('execute');
        $this->revertGiftCardAccountBalanceMock->expects($this->exactly($customerId ? 0 : 1))
            ->method('execute')
            ->with($this->observerMock);

        $this->model->execute($this->observerMock);
    }

    public function executeDataProvider() : array
    {
        return [[1], [null]];
    }
}
