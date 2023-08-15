<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model\Total\Creditmemo;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Model\Total\Creditmemo\Giftcardaccount;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftcardaccountTest extends TestCase
{
    /**
     * @var Giftcardaccount
     */
    private $giftcardaccount;

    /**
     * @var Creditmemo|MockObject
     */
    private $creditmemoMock;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->giftcardaccount = $objectManagerHelper->getObject(Giftcardaccount::class);

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseGiftCardsAmount', 'getBaseGiftCardsInvoiced', 'getBaseGiftCardsRefunded'])
            ->getMock();

        $this->creditmemoMock = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getBaseGrandTotal', 'getGrandTotal'])
            ->getMock();
        $this->creditmemoMock->method('getOrder')
            ->willReturn($this->orderMock);
    }

    /**
     * Calculation GiftCardsAmount and BaseGiftCardsAmount for the credit memo.
     * The case when (GiftCardsInvoiced - GiftCardsRefunded) more than GrandTotal.
     */
    public function testCollectForGiftCardsInvoicedMoreGrandTotal()
    {
        $baseGiftCardsAmount = 25;
        $baseGiftCardsRefundedAmount = 15;
        $baseGrantTotal = 10;
        $grantTotal = 5;

        $this->orderMock->method('getBaseGiftCardsAmount')
            ->willReturn($baseGiftCardsAmount);
        $this->orderMock->method('getBaseGiftCardsInvoiced')
            ->willReturn($baseGiftCardsAmount);
        $this->orderMock->method('getBaseGiftCardsRefunded')
            ->willReturn($baseGiftCardsRefundedAmount);

        $this->creditmemoMock->method('getBaseGrandTotal')
            ->willReturn($baseGrantTotal);
        $this->creditmemoMock->method('getGrandTotal')
            ->willReturn($grantTotal);

        $this->giftcardaccount->collect($this->creditmemoMock);

        $this->assertEquals($this->creditmemoMock->getBaseGiftCardsAmount(), $baseGrantTotal);
        $this->assertEquals($this->creditmemoMock->getGiftCardsAmount(), $grantTotal);
        $this->assertTrue($this->creditmemoMock->getAllowZeroGrandTotal());
    }

    /**
     * Calculation GiftCardsAmount and BaseGiftCardsAmount for the credit memo.
     * The case when (GiftCardsInvoiced - GiftCardsRefunded) less than GrandTotal.
     */
    public function testCollectForGiftCardsInvoicedLessGrandTotal()
    {
        $baseGiftCardsAmount = 10;
        $baseGiftCardsInvoiced = 5;
        $baseGiftCardsRefunded = 5;
        $grantTotal = 15;

        $this->orderMock->method('getBaseGiftCardsAmount')
            ->willReturn($baseGiftCardsAmount);
        $this->orderMock->method('getBaseGiftCardsInvoiced')
            ->willReturn($baseGiftCardsInvoiced);

        $this->orderMock->method('getBaseGiftCardsRefunded')
            ->willReturn($baseGiftCardsRefunded);

        $this->creditmemoMock->method('getBaseGrandTotal')
            ->willReturn($grantTotal);
        $this->creditmemoMock->method('getGrandTotal')
            ->willReturn($grantTotal);

        $this->giftcardaccount->collect($this->creditmemoMock);

        $this->assertEquals(
            $this->creditmemoMock->getBaseGiftCardsAmount(),
            ($baseGiftCardsInvoiced - $baseGiftCardsRefunded)
        );
        $this->assertEquals(
            $this->creditmemoMock->getGiftCardsAmount(),
            ($baseGiftCardsInvoiced - $baseGiftCardsRefunded)
        );
        $this->assertNull($this->creditmemoMock->getAllowZeroGrandTotal());
    }
}
