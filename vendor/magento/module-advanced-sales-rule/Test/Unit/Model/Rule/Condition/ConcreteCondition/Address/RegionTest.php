<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition\Address;

use Magento\AdvancedRule\Helper\Filter;
use Magento\AdvancedRule\Model\Condition\FilterGroup;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Region;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\AbstractCondition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegionTest extends TestCase
{
    const CLASS_NAME = Region::class;

    const EXPECTED_CLASS_NAME =
        \Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Address\Region::class;

    /**
     * @var Region
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
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->filterGroupFactory = $this->getMockBuilder(FilterGroupInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->filterHelper = $this->createMock(Filter::class);
        $this->abstractCondition = $this->getMockForAbstractClass(AbstractCondition::class, [], '', false);
    }

    /**
     * test testIsFilterable
     * @param string $attribute
     * @param string $operator
     * @param array|object|null $valueParsed
     * @param bool $expected
     * @dataProvider isFilterableDataProvider
     */
    public function testIsFilterable($attribute, $operator, $valueParsed, $expected)
    {
        $this->abstractCondition->setData('attribute', $attribute);
        $this->abstractCondition->setData('operator', $operator);
        $this->abstractCondition->setData('value_parsed', $valueParsed);

        $this->model = $this->objectManager->getObject(
            self::CLASS_NAME,
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
    public function isFilterableDataProvider()
    {
        return [
            'array_val_equal_not_filterable' => ['region', '==', [3], false],
            'obj_val_equal_not_filterable' => ['region', '==', new \stdClass(), false],
            'null_val_equal_not_filterable' => ['region', '==', null, false],
            'string_val_equal_filterable' => ['region', '==', 'string', true],

            'array_val_not_equal_not_filterable' => ['region', '!=', [3], false],
            'obj_val_not_equal_not_filterable' => ['region', '!=', new \stdClass(), false],
            'null_val_not_equal_not_filterable' => ['region', '!=', null, false],
            'string_val_not_equal_filterable' => ['region', '!=', 'string', true],

            'string_val_greater_equal_not_filterable' => ['region', '>=', 'string', false],
            'array_val_greater_equal_not_filterable' => ['region', '>=', [3], false],
        ];
    }

    /**
     * test GetFilterGroups
     * @param string $operator
     * @dataProvider getFilterGroupsDataProvider
     */
    public function testGetFilterGroups($operator)
    {
        $this->abstractCondition->setData('operator', $operator);
        $this->abstractCondition->setData('attribute', 'address');
        $this->abstractCondition->setData('value_parsed', '1');

        $this->model = $this->objectManager->getObject(
            self::CLASS_NAME,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'condition' => $this->abstractCondition,
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
            ->with('quote_address:address:1')
            ->willReturnSelf();

        $filter->expects($this->any())
            ->method('setWeight')
            ->willReturnSelf();

        //test getFilterTextGeneratorClass
        $filter->expects($this->any())
            ->method('setFilterTextGeneratorClass')
            ->with(self::EXPECTED_CLASS_NAME)
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
    public function getFilterGroupsDataProvider()
    {
        return [
            'equal' => ['=='],
            'not_equal' => ['!='],
            'greater_than' => ['>=']
        ];
    }
}
