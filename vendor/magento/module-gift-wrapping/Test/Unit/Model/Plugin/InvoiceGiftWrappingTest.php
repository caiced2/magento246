<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Plugin;

use Magento\GiftWrapping\Model\Plugin\InvoiceGiftWrapping;
use Magento\Sales\Api\Data\InvoiceExtension;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class InvoiceGiftWrappingTest extends TestCase
{
    /**
     * @var InvoiceGiftWrapping
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
     * @var InvoiceExtension|MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var InvoiceExtensionFactory|MockObject
     */
    private $invoiceExtensionFactoryMock;

    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockForAbstractClass(InvoiceRepositoryInterface::class);
        $this->invoiceMock = $this->getMockBuilder(InvoiceInterface::class)
            ->setMethods([
                'getGwBasePrice',
                'getGwPrice',
                'getGwItemsBasePrice',
                'getGwItemsPrice',
                'getGwCardBasePrice',
                'getGwCardPrice',
                'getGwBaseTaxAmount',
                'getGwTaxAmount',
                'getGwItemsBaseTaxAmount',
                'getGwItemsTaxAmount',
                'getGwCardBaseTaxAmount',
                'getGwCardTaxAmount',
                'getExtensionAttributes',
                'setExtensionAttributes',
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributeMock = $this->getMockBuilder(InvoiceExtension::class)
            ->setMethods([
                'setGwBasePrice',
                'setGwPrice',
                'setGwItemsBasePrice',
                'setGwItemsPrice',
                'setGwCardBasePrice',
                'setGwCardPrice',
                'setGwBaseTaxAmount',
                'setGwTaxAmount',
                'setGwItemsBaseTaxAmount',
                'setGwItemsTaxAmount',
                'setGwCardBaseTaxAmount',
                'setGwCardTaxAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceExtensionFactoryMock = $this->getMockBuilder(InvoiceExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new InvoiceGiftWrapping(
            $this->invoiceExtensionFactoryMock
        );
    }

    public function testAfterGet()
    {
        $returnValue = 10;

        $this->invoiceMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);

        $this->invoiceMock->expects(static::once())
            ->method('getGwBasePrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwBasePrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwPrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwPrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwItemsBasePrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwItemsBasePrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwItemsPrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwItemsPrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwCardBasePrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwCardBasePrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwCardPrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwCardPrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwBaseTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwBaseTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwItemsBaseTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwItemsBaseTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwItemsTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwItemsTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwCardBaseTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwCardBaseTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('getGwCardTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwCardTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();

        $this->invoiceMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->invoiceMock);
    }
}
