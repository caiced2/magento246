<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition\Product;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Factory;
use Magento\AdvancedSalesRule\Model\Rule\Condition\Product;
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
     * @var Product|MockObject
     */
    protected $productCondition;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $className = ObjectManagerInterface::class;
        $this->objectManagerInterface = $this->createMock($className);

        $className = Product::class;
        $this->productCondition = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->addMethods(['get', 'getOperator'])
            ->onlyMethods(['getAttribute', 'getValueParsed'])
            ->getMock();

        $this->model = $this->objectManager->getObject(
            Factory::class,
            [
                'objectManager' => $this->objectManagerInterface,
            ]
        );
    }

    /**
     * test Create Category
     */
    public function testCreateCategory()
    {
        $this->productCondition->expects($this->any())
            ->method('getAttribute')
            ->willReturn('category_ids');

        $this->productCondition->expects($this->any())
            ->method('getOperator')
            ->willReturn('==');

        $this->productCondition->expects($this->any())
            ->method('getValueParsed')
            ->willReturn([3, 4, 5]);

        $this->objectManagerInterface->expects($this->any())
            ->method('create')
            ->with(
                Categories::class,
                $this->arrayHasKey('data')
            )
            ->willReturn(
                $this->createMock(
                    Categories::class
                )
            );

        $object = $this->model->create($this->productCondition);
        $this->assertNotNull($object);
    }

    /**
     * test Create Default
     * @param string $attribute
     * @dataProvider createDefaultDataProvider
     */
    public function testCreateDefault($attribute)
    {
        $this->productCondition->expects($this->any())
            ->method('getAttribute')
            ->willReturn($attribute);

        $this->productCondition->expects($this->any())
            ->method('getOperator')
            ->willReturn('==');

        $this->objectManagerInterface->expects($this->any())
            ->method('create')
            ->with(DefaultCondition::class)
            ->willReturn(
                $this->createMock(
                    DefaultCondition::class
                )
            );

        $object = $this->model->create($this->productCondition);
        $this->assertNotNull($object);
    }

    /**
     * @return array
     */
    public function createDefaultDataProvider()
    {
        return [
            'quote_item_qty' => ['quote_item_qty'],
            'quote_item_price' => ['quote_item_price'],
            'quote_item_row_total' => ['quote_item_row_total'],
        ];
    }

    /**
     * test Create Attribute
     */
    public function testCreateAttribute()
    {
        $this->productCondition->expects($this->any())
            ->method('getAttribute')
            ->willReturn('sku');

        $this->productCondition->expects($this->any())
            ->method('getOperator')
            ->willReturn('==');

        $this->objectManagerInterface->expects($this->any())
            ->method('create')
            ->with(
                Attribute::class,
                ['condition' => $this->productCondition]
            )
            ->willReturn(
                $this->createMock(
                    Attribute::class
                )
            );

        $object = $this->model->create($this->productCondition);
        $this->assertNotNull($object);
    }
}
