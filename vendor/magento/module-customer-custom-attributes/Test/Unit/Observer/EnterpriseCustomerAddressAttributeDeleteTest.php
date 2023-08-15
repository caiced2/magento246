<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

use Magento\Customer\Model\Attribute;
use Magento\CustomerCustomAttributes\Model\Sales\Order\Address;
use Magento\CustomerCustomAttributes\Model\Sales\Quote\AddressFactory;
use Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAddressAttributeDelete;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnterpriseCustomerAddressAttributeDeleteTest extends TestCase
{
    /**
     * @var EnterpriseCustomerAddressAttributeDelete
     */
    protected $observer;

    /**
     * @var MockObject
     */
    protected $quoteAddressFactory;

    /**
     * @var MockObject
     */
    protected $orderAddressFactory;

    protected function setUp(): void
    {
        $this->quoteAddressFactory = $this->getMockBuilder(
            AddressFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->orderAddressFactory = $this->getMockBuilder(
            \Magento\CustomerCustomAttributes\Model\Sales\Order\AddressFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->observer = new EnterpriseCustomerAddressAttributeDelete(
            $this->orderAddressFactory,
            $this->quoteAddressFactory
        );
    }

    public function testEnterpriseCustomerAddressAttributeDelete()
    {
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(Attribute::class)
            ->setMethods(['isObjectNew'])
            ->disableOriginalConstructor()
            ->getMock();

        $orderAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteAddress = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Model\Sales\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('isObjectNew')->willReturn(false);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getAttribute')->willReturn($dataModel);
        $quoteAddress->expects($this->once())->method('deleteAttribute')->with($dataModel)->willReturnSelf();
        $this->quoteAddressFactory->expects($this->once())->method('create')->willReturn($quoteAddress);
        $orderAddress->expects($this->once())->method('deleteAttribute')->with($dataModel)->willReturnSelf();
        $this->orderAddressFactory->expects($this->once())->method('create')->willReturn($orderAddress);
        /** @var Observer $observer */
        $this->assertInstanceOf(
            EnterpriseCustomerAddressAttributeDelete::class,
            $this->observer->execute($observer)
        );
    }
}
