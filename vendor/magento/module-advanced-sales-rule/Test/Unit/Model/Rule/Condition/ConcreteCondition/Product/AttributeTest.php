<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition\Product;

use Magento\AdvancedRule\Helper\Filter;
use Magento\AdvancedRule\Model\Condition\FilterGroup;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\AbstractCondition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributeTest extends TestCase
{
    /**
     * @var Attribute
     */
    protected $model;

    /**
     * @var FilterGroupInterfaceFactory|MockObject
     */
    protected $filterGroupFactory;

    /**
     * @var Filter|MockObject
     */
    protected $filterHelper;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var AbstractCondition|MockObject
     */
    protected $abstractCondition;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $className = FilterGroupInterfaceFactory::class;
        $this->filterGroupFactory = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $className = Filter::class;
        $this->filterHelper = $this->createMock($className);

        $className = AbstractCondition::class;
        $this->abstractCondition = $this->getMockForAbstractClass($className, [], '', false);
    }

    /**
     * test testIsFilterable.
     *
     * @param string $attribute
     * @param string $operator
     * @param array|object|null $valueParsed
     * @param bool $expected
     *
     * @return void
     * @dataProvider isFilterableDataProvider
     */
    public function testIsFilterable($attribute, $operator, $valueParsed, $expected): void
    {
        $this->abstractCondition->setData('attribute', $attribute);
        $this->abstractCondition->setData('operator', $operator);
        $this->abstractCondition->setData('value_parsed', $valueParsed);

        $this->model = $this->objectManager->getObject(
            Attribute::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'condition' => $this->abstractCondition,
            ]
        );

        $this->assertEquals($expected, $this->model->isFilterable());
    }

    /**
     * @return array
     */
    public function isFilterableDataProvider(): array
    {
        return [
            'array_val_equal_not_filterable' => ['sku', '==', [3], false],
            'obj_val_equal_not_filterable' => ['sku', '==', new \stdClass(), false],
            'null_val_equal_not_filterable' => ['sku', '==', null, false],
            'string_val_equal_filterable' => ['sku', '==', 'string', true],

            'array_val_not_equal_not_filterable' => ['sku', '!=', [3], false],
            'obj_val_not_equal_not_filterable' => ['sku', '!=', new \stdClass(), false],
            'null_val_not_equal_not_filterable' => ['sku', '!=', null, false],
            'string_val_not_equal_not_filterable' => ['sku', '!=', 'string', false],

            'string_val_greater_equal_not_filterable' => ['sku', '>=', 'string', false],
            'array_val_greater_equal_not_filterable' => ['sku', '>=', [3], false]
        ];
    }

    /**
     * test GetFilterGroups.
     *
     * @param string $operator
     *
     * @return void
     * @dataProvider getFilterGroupsDataProvider
     */
    public function testGetFilterGroups($operator): void
    {
        $this->abstractCondition->setData('operator', $operator);
        $this->abstractCondition->setData('attribute', 'sku');
        $this->abstractCondition->setData('value_parsed', '1');

        $this->model = $this->objectManager->getObject(
            Attribute::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'condition' => $this->abstractCondition
            ]
        );

        $filter =
            $this->getMockBuilder(\Magento\AdvancedRule\Model\Condition\Filter::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['setFilterText', 'setWeight', 'setFilterTextGeneratorClass'])
                ->getMock();

        //test getFilterTextPrefix
        $filter->expects($this->any())
            ->method('setFilterText')
            ->with('product:attribute:sku:1')
            ->willReturnSelf();

        $filter->expects($this->any())
            ->method('setWeight')
            ->willReturnSelf();

        //test getFilterTextGeneratorClass
        $filter->expects($this->any())
            ->method('setFilterTextGeneratorClass')
            ->with(\Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Attribute::class)
            ->willReturnSelf();

        $className = FilterGroup::class;
        $filterGroup = $this->createMock($className);

        $this->filterHelper->expects($this->once())
            ->method('createFilter')
            ->willReturn($filter);

        $this->filterGroupFactory->expects($this->any())
            ->method('create')
            ->willReturn($filterGroup);

        if ($operator == '==') {
            $return = $this->model->getFilterGroups();
            $this->assertIsArray($return);
            $this->assertSame([$filterGroup], $return);
        } elseif ($operator == '!=') {
            $this->filterHelper->expects($this->any())
                ->method('negateFilter')
                ->with($filter);

            $return = $this->model->getFilterGroups();
            $this->assertIsArray($return);
            $this->assertNotSame([$filterGroup], $return);
        }

        //test caching if (create should be called only once)
        $this->model->getFilterGroups();
    }

    /**
     * @return array
     */
    public function getFilterGroupsDataProvider(): array
    {
        return [
            'equal' => ['=='],
            'not_equal' => ['!='],
            'greater_than' => ['>=']
        ];
    }
}
