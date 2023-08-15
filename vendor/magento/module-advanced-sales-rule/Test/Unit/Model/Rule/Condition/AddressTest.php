<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition;

use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedSalesRule\Model\Rule\Condition\Address;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Factory;
use Magento\Directory\Model\Config\Source\Allregion;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\Context;
use Magento\Shipping\Model\Config\Source\Allmethods;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    /**
     * @var Address
     */
    protected $model;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var Country|MockObject
     */
    protected $directoryCountry;

    /**
     * @var Allregion|MockObject
     */
    protected $directoryAllregion;

    /**
     * @var Allmethods|MockObject
     */
    protected $shippingAllmethods;

    /**
     * @var \Magento\Payment\Model\Config\Source\Allmethods|MockObject
     */
    protected $paymentAllmethods;

    /**
     * @var Factory|MockObject
     */
    protected $concreteConditionFactory;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->context = $this->createMock(Context::class);
        $this->directoryCountry = $this->createMock(Country::class);
        $this->directoryAllregion = $this->createMock(Allregion::class);
        $this->shippingAllmethods = $this->createMock(Allmethods::class);
        $this->paymentAllmethods = $this->createMock(\Magento\Payment\Model\Config\Source\Allmethods::class);
        $this->concreteConditionFactory = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->model = $this->objectManager->getObject(
            Address::class,
            [
                'context' => $this->context,
                'directoryCountry' => $this->directoryCountry,
                'directoryAllregion' => $this->directoryAllregion,
                'shippingAllmethods' => $this->shippingAllmethods,
                'paymentAllmethods' => $this->paymentAllmethods,
                'concreteConditionFactory' => $this->concreteConditionFactory,
                'data' => [],
            ]
        );
    }

    /**
     * test IsFilterable
     */
    public function testIsFilterable()
    {
        $className = FilterableConditionInterface::class;
        $interface =$this->createMock($className);

        $interface->expects($this->any())
            ->method('isFilterable')
            ->willReturn(true);

        $this->concreteConditionFactory->expects($this->any())
            ->method('create')
            ->willReturn($interface);

        $this->assertTrue($this->model->isFilterable());
    }

    /**
     * test GetFilterGroups
     */
    public function testGetFilterGroups()
    {
        $className = FilterGroupInterface::class;
        $filterGroupInterface =$this->createMock($className);

        $className = FilterableConditionInterface::class;
        $interface =$this->createMock($className);

        $interface->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn([$filterGroupInterface]);

        $this->concreteConditionFactory->expects($this->any())
            ->method('create')
            ->willReturn($interface);

        $this->assertEquals([$filterGroupInterface], $this->model->getFilterGroups());
    }
}
