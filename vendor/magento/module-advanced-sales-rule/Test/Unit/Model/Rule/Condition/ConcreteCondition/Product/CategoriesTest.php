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
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories;
use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Category;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoriesTest extends TestCase
{
    /**
     * @var Categories
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
     * @var array
     */
    protected $data;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * {@inheritdoc}
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
    }

    /**
     * Test testIsFilterable method.
     *
     * @param string $operator
     * @param bool $expected
     * @dataProvider isFilterableDataProvider
     */
    public function testIsFilterable($operator, $expected)
    {
        $this->data = ['operator' => $operator, 'categories'=> null];
        $this->model = $this->objectManager->getObject(
            Categories::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'data' => $this->data,
            ]
        );

        $this->assertEquals($expected, $this->model->isFilterable());
    }

    /**
     * Data provider for isFilterable test.
     *
     * @return array
     */
    public function isFilterableDataProvider()
    {
        return [
            ['()', true],
            ['==', true],
            ['!=', false],
            ['!()', false],
            ['>=', false],
            ['>=', false],
        ];
    }

    /**
     * Test GetFilterGroups method.
     *
     * @param string $operator
     * @dataProvider getFilterGroupsDataProvider
     */
    public function testGetFilterGroups($operator)
    {
        $this->data = ['operator' => $operator, 'categories'=> [1]];

        $this->model = $this->objectManager->getObject(
            Categories::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'data' => $this->data,
            ]
        );

        $filter =
            $this->getMockBuilder(\Magento\AdvancedRule\Model\Condition\Filter::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['setFilterText', 'setWeight', 'setFilterTextGeneratorClass'])
                ->getMock();

        //test getFilterTextPrefix
        $filter->expects($this->once())
            ->method('setFilterText')
            ->with('product:category:1')
            ->willReturnSelf();

        $filter->expects($this->any())
            ->method('setWeight')
            ->willReturnSelf();

        //test getFilterTextGeneratorClass
        $filter->expects($this->any())
            ->method('setFilterTextGeneratorClass')
            ->with(Category::class)
            ->willReturnSelf();

        $className = FilterGroup::class;
        $filterGroup = $this->createMock($className);

        $this->filterHelper->expects($this->once())
            ->method('createFilter')
            ->willReturn($filter);

        $this->filterGroupFactory->expects($this->once())
            ->method('create')
            ->willReturn($filterGroup);

        $this->filterHelper->expects($this->any())
            ->method('negateFilter')
            ->with($filter);

        $return = $this->model->getFilterGroups();
        $this->assertIsArray($return);
        $this->assertSame([$filterGroup], $return);

        //test caching if (create should be called only once)
        $this->model->getFilterGroups();
    }

    /**
     * Data provider for getFilterGroups test.
     *
     * @return array
     */
    public function getFilterGroupsDataProvider()
    {
        return [
            'equal' => [
                'operator' => '==',
            ],
            'in' => [
                'operator' => '()',
            ],
        ];
    }

    /**
     * Test GetFilterGroups when the condition is not filterable.
     *
     * @param string $operator
     * @dataProvider getFilterGroupsNonFilterableDataProvider
     */
    public function testGetFilterGroupsNonFilterable($operator)
    {
        $this->data = ['operator' => $operator, 'categories'=> [1]];

        $this->model = $this->objectManager->getObject(
            Categories::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'data' => $this->data,
            ]
        );

        $return = $this->model->getFilterGroups();
        $this->assertIsArray($return);
        $this->assertSame([], $return);

        //test caching if (create should be called only once)
        $this->model->getFilterGroups();
    }

    /**
     * Test GetFilterGroups method when categories are empty.
     *
     * @param null|array $categories
     * @dataProvider getFilterGroupsWhenCategoriesAreEmptyDataProvider
     * @return void
     */
    public function testGetFilterGroupsWhenCategoriesAreEmpty($categories)
    {
        $expects = [];

        $this->data = ['operator' => null, 'categories'=> $categories];

        $this->model = $this->objectManager->getObject(
            Categories::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'data' => $this->data,
            ]
        );

        $this->assertEquals($expects, $this->model->getFilterGroups());
    }

    /**
     * Data provider for testGetFilterGroupsWhenCategoriesAreEmpty test.
     *
     * @return array
     */
    public function getFilterGroupsWhenCategoriesAreEmptyDataProvider()
    {
        return [
            0 => [null],
            1 => [[]]
        ];
    }

    /**
     * Data provider for getFilterGroupsNonFilterable test.
     *
     * @return array
     */
    public function getFilterGroupsNonFilterableDataProvider()
    {
        return [
            'not_equal' => ['!='],
            'not_group' => ['!()'],
            'greater_equal' => ['>='],
        ];
    }
}
