<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\GiftCardAccount\Model\History;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

/**
 * Giftcard account history model test
 */
class HistoryTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * Tests that additional info contains order information
     *
     * @return void
     */
    public function testBeforeSaveAdditionalInfo(): void
    {
        $orderId = 9999999;
        $sessionMock = $this->createPartialMock(Session::class, ['getQuote']);
        $model = $this->objectManager->getObject(
            History::class,
            [
                'checkoutSession' => $sessionMock,
            ]
        );
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getReservedOrderId'])
            ->getMock();
        $sessionMock->method('getQuote')
            ->willReturn($quoteMock);
        $quoteMock->method('getReservedOrderId')
            ->willReturn($orderId);
        $giftCardAccountMock = $this->getMockBuilder(Giftcardaccount::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHistoryAction'])
            ->getMock();
        $giftCardAccountMock->method('getHistoryAction')
            ->willReturn(History::ACTION_CREATED);
        $model->setGiftcardaccount($giftCardAccountMock);
        $model->beforeSave();
        $this->assertEquals(__('Order #%1.', $orderId), $model->getAdditionalInfo());
    }
}
