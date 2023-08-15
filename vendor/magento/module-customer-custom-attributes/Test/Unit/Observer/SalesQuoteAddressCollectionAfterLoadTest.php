<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

use Magento\CustomerCustomAttributes\Model\Sales\Quote\Address;
use Magento\CustomerCustomAttributes\Model\Sales\Quote\AddressFactory;
use Magento\CustomerCustomAttributes\Observer\SalesQuoteAddressCollectionAfterLoad;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesQuoteAddressCollectionAfterLoadTest extends TestCase
{
    /**
     * @var SalesQuoteAddressCollectionAfterLoad
     */
    protected $observer;

    /**
     * @var MockObject
     */
    protected $quoteAddressFactory;

    protected function setUp(): void
    {
        $this->quoteAddressFactory = $this->getMockBuilder(
            AddressFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->observer = new SalesQuoteAddressCollectionAfterLoad(
            $this->quoteAddressFactory
        );
    }

    public function testSalesQuoteAddressCollectionAfterLoad()
    {
        $items = ['test', 'data'];
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getQuoteAddressCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(AbstractDb::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $quoteAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('getItems')->willReturn($items);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getQuoteAddressCollection')->willReturn($dataModel);
        $quoteAddress->expects($this->once())->method('attachDataToEntities')->with($items)->willReturnSelf();
        $this->quoteAddressFactory->expects($this->once())->method('create')->willReturn($quoteAddress);
        /** @var Observer $observer */
        $this->assertInstanceOf(
            SalesQuoteAddressCollectionAfterLoad::class,
            $this->observer->execute($observer)
        );
    }
}
