<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

use Magento\CustomerCustomAttributes\Helper\Data;
use Magento\CustomerCustomAttributes\Observer\AbstractObserver;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CoreCopyMethodsTest extends TestCase
{
    /**
     * @var AbstractObserver
     */
    protected $observer;

    /**
     * @var MockObject
     */
    protected $quoteAddressFactory;

    /**
     * @var MockObject
     */
    protected $helper;

    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function coreCopyMethodsDataProvider()
    {
        return [
            'CoreCopyFieldsetSalesConvertQuoteToOrder' => [
                'CoreCopyFieldsetSalesConvertQuoteToOrder',
                'getCustomerUserDefinedAttributeCodes',
                'customer_',
                'customer_',
            ],
            'CoreCopyFieldsetSalesCopyOrderToEdit' => [
                'CoreCopyFieldsetSalesCopyOrderToEdit',
                'getCustomerUserDefinedAttributeCodes',
                'customer_',
                'customer_',
            ],
            'CoreCopyFieldsetCustomerAccountToQuote' => [
                'CoreCopyFieldsetCustomerAccountToQuote',
                'getCustomerUserDefinedAttributeCodes',
                '',
                'customer_',
            ],
            'CoreCopyFieldsetCheckoutOnepageQuoteToCustomer' => [
                'CoreCopyFieldsetCheckoutOnepageQuoteToCustomer',
                'getCustomerUserDefinedAttributeCodes',
                'customer_',
                '',
            ],
            'CoreCopyFieldsetSalesConvertQuoteAddressToOrderAddress' => [
                'CoreCopyFieldsetSalesConvertQuoteAddressToOrderAddress',
                'getCustomerAddressUserDefinedAttributeCodes',
                '',
                '',
            ],
            'CoreCopyFieldsetSalesCopyOrderBillingAddressToOrder' => [
                'CoreCopyFieldsetSalesCopyOrderBillingAddressToOrder',
                'getCustomerAddressUserDefinedAttributeCodes',
                '',
                '',
            ],
            'CoreCopyFieldsetSalesCopyOrderShippingAddressToOrder' => [
                'CoreCopyFieldsetSalesCopyOrderShippingAddressToOrder',
                'getCustomerAddressUserDefinedAttributeCodes',
                '',
                '',
            ],
            'CoreCopyFieldsetCustomerAddressToQuoteAddress' => [
                'CoreCopyFieldsetCustomerAddressToQuoteAddress',
                'getCustomerAddressUserDefinedAttributeCodes',
                '',
                '',
            ],
            'CoreCopyFieldsetQuoteAddressToCustomerAddress' => [
                'CoreCopyFieldsetQuoteAddressToCustomerAddress',
                'getCustomerAddressUserDefinedAttributeCodes',
                '',
                '',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider coreCopyMethodsDataProvider
     *
     * @param string $testableObserverClass
     * @param string $helperMethod
     * @param string $sourcePrefix
     * @param string $targetPrefix
     */
    public function testCoreCopyMethods($testableObserverClass, $helperMethod, $sourcePrefix, $targetPrefix)
    {
        $className = '\Magento\CustomerCustomAttributes\Observer\\' . $testableObserverClass;
        $this->observer = new $className(
            $this->helper
        );

        $attribute = 'testAttribute';
        $attributeData = 'data';
        $attributes = [$attribute];
        $sourceAttributeWithPrefix = $sourcePrefix . $attribute;
        $targetAttributeWithPrefix = $targetPrefix . $attribute;

        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getSource', 'getTarget'])
            ->disableOriginalConstructor()
            ->getMock();

        $sourceModel = $this->getMockBuilder(AbstractModel::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $targetModel = $this->getMockBuilder(AbstractModel::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper->expects($this->once())
            ->method($helperMethod)
            ->willReturn($attributes);
        $sourceModel->expects($this->once())
            ->method('getData')
            ->with($sourceAttributeWithPrefix)
            ->willReturn($attributeData);
        $targetModel->expects($this->once())
            ->method('setData')
            ->with($this->logicalOr($targetAttributeWithPrefix, $attributeData))->willReturnSelf();
        $observer->expects($this->exactly(2))->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getSource')->willReturn($sourceModel);
        $event->expects($this->once())->method('getTarget')->willReturn($targetModel);
        /** @var Observer $observer */
        $this->assertInstanceOf(
            $className,
            $this->observer->execute($observer)
        );
    }
}
