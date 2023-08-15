<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model\Plugin;

use Magento\GiftCardAccount\Model\Plugin\InvoiceRepository;
use Magento\Sales\Api\Data\InvoiceExtension;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceSearchResultInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Invoice repository plugin
 */
class InvoiceRepositoryTest extends TestCase
{
    /**
     * @var InvoiceRepository
     */
    private $plugin;

    /**
     * @var InvoiceRepositoryInterface|MockObject
     */
    private $subjectMock;

    /**
     * @var InvoiceInterface|MockObject
     */
    private $invoiceMock;

    /**
     * @var float
     */
    private $giftCardsAmount = 10;

    /**
     * @var float
     */
    private $baseGiftCardsAmount = 15;

    /**
     * @var InvoiceExtension|MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var InvoiceSearchResultInterface|MockObject
     */
    private $invoiceSearchResultMock;

    /**
     * @var InvoiceExtensionFactory|MockObject
     */
    private $invoiceExtensionFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockForAbstractClass(
            InvoiceRepositoryInterface::class
        );

        $this->invoiceMock = $this->getMockBuilder(InvoiceInterface::class)
            ->setMethods([
                'getExtensionAttributes',
                'setExtensionAttributes',
                'setGiftCardsAmount',
                'setBaseGiftCardsAmount',
                'getBaseGiftCardsAmount',
                'getGiftCardsAmount'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributeMock = $this->getMockBuilder(InvoiceExtension::class)
            ->setMethods([
                'getGiftCardsAmount',
                'getBaseGiftCardsAmount',
                'setGiftCardsAmount',
                'setBaseGiftCardsAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceSearchResultMock = $this->getMockForAbstractClass(
            InvoiceSearchResultInterface::class
        );

        $this->invoiceExtensionFactoryMock = $this->getMockBuilder(InvoiceExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new InvoiceRepository(
            $this->invoiceExtensionFactoryMock
        );
    }

    public function testAfterGet()
    {
        $this->invoiceMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->invoiceMock->expects($this->once())
            ->method('getGiftCardsAmount')
            ->willReturn($this->giftCardsAmount);
        $this->invoiceMock->expects($this->once())
            ->method('getBaseGiftCardsAmount')
            ->willReturn($this->baseGiftCardsAmount);

        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCardsAmount')
            ->with($this->giftCardsAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setBaseGiftCardsAmount')
            ->with($this->baseGiftCardsAmount)
            ->willReturnSelf();

        $this->invoiceMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->invoiceMock);
    }

    public function testAfterGetList()
    {
        $this->invoiceSearchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->invoiceMock]);

        $this->invoiceMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->invoiceMock->expects($this->once())
            ->method('getGiftCardsAmount')
            ->willReturn($this->giftCardsAmount);
        $this->invoiceMock->expects($this->once())
            ->method('getBaseGiftCardsAmount')
            ->willReturn($this->baseGiftCardsAmount);

        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCardsAmount')
            ->with($this->giftCardsAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setBaseGiftCardsAmount')
            ->with($this->baseGiftCardsAmount)
            ->willReturnSelf();

        $this->invoiceMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGetList($this->subjectMock, $this->invoiceSearchResultMock);
    }

    public function testBeforeSave()
    {
        $this->invoiceMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);

        $this->extensionAttributeMock->method('getGiftCardsAmount')
            ->willReturn($this->giftCardsAmount);
        $this->extensionAttributeMock->method('getBaseGiftCardsAmount')
            ->willReturn($this->baseGiftCardsAmount);

        $this->invoiceMock->expects($this->once())
            ->method('setGiftCardsAmount')
            ->with($this->giftCardsAmount)
            ->willReturnSelf();
        $this->invoiceMock->expects($this->once())
            ->method('setBaseGiftCardsAmount')
            ->with($this->baseGiftCardsAmount)
            ->willReturnSelf();

        $this->plugin->beforeSave($this->subjectMock, $this->invoiceMock);
    }
}
