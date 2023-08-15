<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition\Address;

use Magento\AdvancedSalesRule\Model\Rule\Condition\Address;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\CountryId;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Factory;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\PaymentMethod;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Postcode;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Region;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\RegionId;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\ShippingMethod;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    /**
     * @var Factory
     */
    protected $model;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerInterface;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Address|MockObject
     */
    protected $addressCondition;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->objectManagerInterface = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->addressCondition = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->addMethods(['get', 'getAttribute'])
            ->getMock();

        $this->model = $this->objectManager->getObject(
            Factory::class,
            [
                'objectManager' => $this->objectManagerInterface,
            ]
        );
    }

    /**
     * test Create Default
     * @param string $attribute
     * @dataProvider createDefaultDataProvider
     */
    public function testCreateDefault($attribute)
    {
        $this->addressCondition->expects($this->any())
            ->method('getAttribute')
            ->willReturn($attribute);

        $this->objectManagerInterface->expects($this->any())
            ->method('create')
            ->with(DefaultCondition::class)
            ->willReturn(
                $this->createMock(
                    DefaultCondition::class
                )
            );

        $object = $this->model->create($this->addressCondition);
        $this->assertNotNull($object);
    }

    /**
     * @return array
     */
    public function createDefaultDataProvider()
    {
        return [
            'attribute_default' => ['default'],
            'attribute_sku' => ['sku'],
            'attribute_address' => ['address'],
        ];
    }

    /**
     * test Create Default
     * @param string $attribute
     * @param string $class
     * @dataProvider createAddressDataProvider
     */
    public function testCreateAddress($attribute, $class)
    {
        $this->addressCondition->expects($this->any())
            ->method('getAttribute')
            ->willReturn($attribute);

        $this->objectManagerInterface->expects($this->any())
            ->method('create')
            ->with($class, ['condition' => $this->addressCondition])
            ->willReturn(
                $this->createMock(
                    DefaultCondition::class
                )
            );

        $object = $this->model->create($this->addressCondition);
        $this->assertNotNull($object);
    }

    /**
     * @return array
     */
    public function createAddressDataProvider()
    {
        return [
            'payment_method' => [
                'payment_method',
                PaymentMethod::class
            ],
            'shipping_method' => [
                'shipping_method',
                ShippingMethod::class
            ],
            'country_id' => [
                'country_id',
                CountryId::class
            ],
            'region_id' => [
                'region_id',
                RegionId::class
            ],
            'postcode' => [
                'postcode',
                Postcode::class
            ],
            'region' => [
                'region',
                Region::class
            ]
        ];
    }
}
