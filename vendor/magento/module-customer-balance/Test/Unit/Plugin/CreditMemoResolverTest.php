<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Plugin;

use Magento\CustomerBalance\Plugin\CreditMemoResolver;
use Magento\Framework\Math\FloatComparator;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditMemoResolverTest extends TestCase
{
    /**
     * @var CreditMemoResolver
     */
    private $plugin;

    /**
     * @var FloatComparator|MockObject
     */
    private $comparatorMock;

    /**
     * @var Order|MockObject
     */
    private $subjectMock;

    /**
     * Create mocks and plugin
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->comparatorMock = $this->createMock(FloatComparator::class);

        $this->subjectMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getBaseTotalInvoiced',
                'getBaseTotalRefunded'
            ])
            ->addMethods([
                'getBaseRwrdCrrncyAmtRefunded',
                'getBaseCustomerBalanceRefunded',
                'getBaseRwrdCrrncyAmtInvoiced',
                'getBaseCustomerBalanceInvoiced',
                'getBaseGiftCardsInvoiced',
                'getBaseRwrdCrrncyAmntRefnded',
                'getBaseGiftCardsRefunded',
            ])
            ->getMock();

        $this->plugin = new CreditMemoResolver(
            $this->comparatorMock
        );
    }

    /**
     * Test a case only if credit memo can be created
     *
     * @return void
     */
    public function testAfterCanCreditmemoIfCreditMemoCanBeCreated(): void
    {
        $this->assertFalse($this->plugin->afterCanCreditmemo($this->subjectMock, false));
    }

    /**
     * Test case only if reward points and customer balance were refunded
     *
     * @param $rwrdAmount
     * @param $custBalAmount
     * @return void
     * @dataProvider conditionsDataProvider
     */
    public function testAfterCanCreditmemoForRewardPointsAndCustomerBalanceRefunded($rwrdAmount, $custBalAmount): void
    {
        $this->subjectMock->expects($this->once())
            ->method('getBaseRwrdCrrncyAmtRefunded')
            ->willReturn($rwrdAmount);
        $this->subjectMock->expects($this->once())
            ->method('getBaseCustomerBalanceRefunded')
            ->willReturn($custBalAmount);

        $this->assertTrue($this->plugin->afterCanCreditmemo($this->subjectMock, true));
    }

    /**
     * @return array
     */
    public function conditionsDataProvider(): array
    {
        return [
            'conditionPassed' => [null, null],
            'conditionFailed' => [null, 100.0],
        ];
    }

    /**
     * Test for Magento\CustomerBalance\Plugin\CreditMemoResolver::CreditMemoResolver()
     *
     * @param $zeroAmount
     * @param $invoicedAmount
     * @param $refundAmount
     * @param $gt
     * @param $eq
     * @param $assert
     * @return void
     * @dataProvider creditMemoDataProvider
     */
    public function testcAfterCanCreditmemo($zeroAmount, $invoicedAmount, $refundAmount, $gt, $eq, $assert): void
    {
        $this->subjectMock->expects($this->once())
            ->method('getBaseRwrdCrrncyAmtRefunded')
            ->willReturn(null);
        $this->subjectMock->expects($this->exactly(2))
            ->method('getBaseCustomerBalanceRefunded')
            ->willReturn($zeroAmount);

        $this->subjectMock->expects($this->once())
            ->method('getBaseTotalInvoiced')
            ->willReturn($zeroAmount);
        $this->subjectMock->expects($this->once())
            ->method('getBaseRwrdCrrncyAmtInvoiced')
            ->willReturn($zeroAmount);
        $this->subjectMock->expects($this->exactly(2))
            ->method('getBaseCustomerBalanceInvoiced')
            ->willReturn($invoicedAmount);
        $this->subjectMock->expects($this->once())
            ->method('getBaseGiftCardsInvoiced')
            ->willReturn($zeroAmount);

        $this->subjectMock->expects($this->once())
            ->method('getBaseTotalRefunded')
            ->willReturn($zeroAmount);
        $this->subjectMock->expects($this->once())
            ->method('getBaseRwrdCrrncyAmntRefnded')
            ->willReturn($zeroAmount);
        $this->subjectMock->expects($this->exactly(2))
            ->method('getBaseCustomerBalanceRefunded')
            ->willReturn($refundAmount);
        $this->subjectMock->expects($this->once())
            ->method('getBaseGiftCardsRefunded')
            ->willReturn($zeroAmount);

        $this->comparatorMock->expects($this->once())
            ->method('greaterThan')
            ->willReturn($gt);
        $this->comparatorMock->expects($this->any())
            ->method('equal')
            ->willReturn($eq);

        $this->assertEquals($assert, $this->plugin->afterCanCreditmemo($this->subjectMock, true));
    }

    /**
     * @return array
     */
    public function creditMemoDataProvider(): array
    {
        return [
            'assertFalse' => [0.0, 100.0, 100.0, false, true, false],
            'assertTrue' => [0.0, 100.0, 0.0, true, false, true],
        ];
    }
}
