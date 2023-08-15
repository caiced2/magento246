<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Model\Plugin;

use Magento\CustomerBalance\Model\Plugin\InvoiceRepository;
use Magento\Sales\Api\Data\InvoiceExtension;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var InvoiceExtension|MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var InvoiceExtensionFactory|MockObject
     */
    private $invoiceExtensionFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockForAbstractClass(InvoiceRepositoryInterface::class);
        $this->invoiceMock = $this->getMockBuilder(InvoiceInterface::class)
            ->setMethods([
                'getExtensionAttributes',
                'setExtensionAttributes',
                'setCustomerBalanceAmount',
                'setBaseCustomerBalanceAmount',
                'getBaseCustomerBalanceAmount',
                'getCustomerBalanceAmount'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributeMock = $this->getMockBuilder(InvoiceExtension::class)
            ->setMethods([
                'getCustomerBalanceAmount',
                'getBaseCustomerBalanceAmount',
                'setCustomerBalanceAmount',
                'setBaseCustomerBalanceAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceExtensionFactoryMock = $this->getMockBuilder(InvoiceExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new InvoiceRepository(
            $this->invoiceExtensionFactoryMock
        );
    }

    /**
     * Test to set customer balance info to invoice before save
     */
    public function testBeforeSave()
    {
        $this->invoiceMock
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->extensionAttributeMock->method('getCustomerBalanceAmount')->willReturn(10);
        $this->extensionAttributeMock->method('getBaseCustomerBalanceAmount')->willReturn(15);
        $this->invoiceMock->expects($this->once())->method('setCustomerBalanceAmount')->with(10)->willReturnSelf();
        $this->invoiceMock->expects($this->once())->method('setBaseCustomerBalanceAmount')->with(15)->willReturnSelf();
        $this->plugin->beforeSave($this->subjectMock, $this->invoiceMock);
    }

    /**
     * Test to set customer balance info to invoice after get
     */
    public function testAfterGet()
    {
        $customerBalanceAmount = 10;
        $baseCustomerBalanceAmount = 15;

        $this->invoiceMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->invoiceMock->expects(static::once())
            ->method('getCustomerBalanceAmount')
            ->willReturn($customerBalanceAmount);
        $this->invoiceMock->expects(static::once())
            ->method('getBaseCustomerBalanceAmount')
            ->willReturn($baseCustomerBalanceAmount);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setCustomerBalanceAmount')
            ->with($customerBalanceAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects(static::once())
            ->method('setBaseCustomerBalanceAmount')
            ->with($baseCustomerBalanceAmount)
            ->willReturnSelf();
        $this->invoiceMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->invoiceMock);
    }
}
