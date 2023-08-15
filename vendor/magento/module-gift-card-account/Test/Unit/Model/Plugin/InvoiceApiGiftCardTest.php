<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model\Plugin;

use Magento\GiftCardAccount\Model\Plugin\InvoiceApiGiftCard;
use Magento\Sales\Api\Data\InvoiceExtension;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order\InvoiceDocumentFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class InvoiceApiGiftCardTest
 *
 * Test plugin for Invoice API to set the gift card amount.
 */
class InvoiceApiGiftCardTest extends TestCase
{
    /**
     * @var InvoiceApiGiftCard
     */
    private $plugin;

    /**
     * @var InvoiceDocumentFactory|MockObject
     */
    private $subjectMock;

    /**
     * @var InvoiceInterface|MockObject
     */
    private $invoiceMock;

    /**
     * @var InvoiceExtension|MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var InvoiceExtensionFactory|MockObject
     */
    private $invoiceExtensionFactoryMock;

    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockBuilder(InvoiceDocumentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->invoiceMock = $this->getMockBuilder(InvoiceInterface::class)
            ->setMethods([
                'getGiftCardsAmount',
                'getBaseGiftCardsAmount'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributeMock = $this->getMockBuilder(InvoiceExtension::class)
            ->setMethods([
                'setGiftCardsAmount',
                'setBaseGiftCardsAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceExtensionFactoryMock = $this->getMockBuilder(InvoiceExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new InvoiceApiGiftCard(
            $this->invoiceExtensionFactoryMock
        );
    }

    /**
     * Test invoice API gift card amount after invoice created
     *
     * @param float $giftCardsAmount
     * @param float $baseGiftsCardsAmount
     * @param bool $isExtensionMockExist
     * @dataProvider invoiceApiGiftCardDataProvider
     */
    public function testAfterCreate(
        float $giftCardsAmount,
        float $baseGiftsCardsAmount,
        bool $isExtensionMockExist
    ): void {
        if (!$isExtensionMockExist) {
            $this->invoiceExtensionFactoryMock->expects($this->any())
                ->method('create')
                ->willReturn($this->extensionAttributeMock);
        }
        $this->invoiceMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->invoiceMock->expects($this->once())
            ->method('getGiftCardsAmount')
            ->willReturn($giftCardsAmount);
        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCardsAmount')
            ->with($giftCardsAmount)
            ->willReturnSelf();
        $this->invoiceMock->expects($this->once())
            ->method('getBaseGiftCardsAmount')
            ->willReturn($baseGiftsCardsAmount);
        $this->extensionAttributeMock->expects($this->once())
            ->method('setBaseGiftCardsAmount')
            ->with($baseGiftsCardsAmount)
            ->willReturnSelf();
        $this->invoiceMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterCreate($this->subjectMock, $this->invoiceMock);
    }

    /**
     * @return array
     */
    public function invoiceApiGiftCardDataProvider(): array
    {
        return [
            'gift card amount with extension attribute' => [ 10.0, 15.0, true ],
            'gift card amount without extension attribute' => [ 20.0, 25.0, false ]
        ];
    }
}
