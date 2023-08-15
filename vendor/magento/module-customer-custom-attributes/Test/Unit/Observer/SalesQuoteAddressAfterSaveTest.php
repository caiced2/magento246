<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Customer\Model\ResourceModel\Form\Attribute\Collection;
use Magento\CustomerCustomAttributes\Model\Sales\Quote\Address;
use Magento\CustomerCustomAttributes\Model\Sales\Quote\AddressFactory;
use Magento\CustomerCustomAttributes\Observer\SalesQuoteAddressAfterSave;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for SalesQuoteAddressAfterSave observer
 */
class SalesQuoteAddressAfterSaveTest extends TestCase
{
    /**
     * @var SalesQuoteAddressAfterSave
     */
    protected $observer;

    /**
     * @var MockObject
     */
    protected $quoteAddressFactory;

    /**
     * @var MockObject
     */
    private $attributeProviderMock;

    protected function setUp(): void
    {
        $this->quoteAddressFactory = $this->getMockBuilder(
            AddressFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->attributeProviderMock = $this->getMockBuilder(
            AttributeMetadataDataProvider::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new SalesQuoteAddressAfterSave(
            $this->quoteAddressFactory,
            $this->attributeProviderMock
        );
    }

    public function testSalesQuoteAddressAfterSave()
    {
        $entityType = 'customer_address';
        $formCode = 'customer_register_address';

        $observer = $this->getMockBuilder(Observer::class)
            ->setMethods(['processComplexAttributes', 'getEvent'])
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getQuoteAddress'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(AbstractModel::class)
            ->setMethods(['__wakeup'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();

        $attributes = [$attributeMock];

        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getQuoteAddress')->willReturn($dataModel);

        $this->attributeProviderMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->with($entityType, $formCode)
            ->willReturn($collectionMock);

        $collectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($attributes));

        $quoteAddress->expects($this->once())->method('saveAttributeData')->with($dataModel)->willReturnSelf();
        $this->quoteAddressFactory->expects($this->once())->method('create')->willReturn($quoteAddress);
        /** @var Observer $observer */
        $this->assertInstanceOf(
            SalesQuoteAddressAfterSave::class,
            $this->observer->execute($observer)
        );
    }
}
