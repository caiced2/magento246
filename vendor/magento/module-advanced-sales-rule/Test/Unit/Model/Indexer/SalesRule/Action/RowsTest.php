<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Indexer\SalesRule\Action;

use Magento\AdvancedRule\Model\Condition\Filter;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\Rows;
use Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter as FilterCondition;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RowsTest extends TestCase
{
    /**
     * @var Rows
     */
    protected $model;

    /**
     * @var Collection|MockObject
     */
    protected $ruleCollection;

    /**
     * @var RuleFactory|MockObject
     */
    protected $ruleFactory;

    /**
     * @var MockObject
     */
    protected $filterResourceModel;

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

        $this->ruleCollection = $this->createMock(Collection::class);

        $this->ruleFactory = $this->getMockBuilder(RuleFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->filterResourceModel = $this->createMock(FilterCondition::class);

        $this->model = $this->objectManager->getObject(
            Rows::class,
            [
                'ruleCollection' => $this->ruleCollection,
                'ruleFactory' => $this->ruleFactory,
                'filterResourceModel' => $this->filterResourceModel,
            ]
        );
    }

    /**
     * test Execute
     */
    public function testExecuteNoInstance()
    {
        $rows = [1];

        $className = Rule::class;
        $rule = $this->createMock($className);

        $this->ruleFactory->expects($this->any())
            ->method('create')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('load')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $rule->expects($this->any())
            ->method('getConditions')
            ->willReturn(false);

        $this->filterResourceModel->expects($this->once())
            ->method('deleteRuleFilters')
            ->with([1]);

        $expectArray = [
            'rule_id' => 1,
            'group_id' => 1,
            'weight' => 1,
            Filter::KEY_FILTER_TEXT => 'true',
            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
        ];

        $this->filterResourceModel->expects($this->once())
            ->method('insertFilters')
            ->with($expectArray);

        $this->model->execute($rows);
    }

    /**
     * test Execute
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithInstance()
    {
        $rows = [1, 2];

        $rule = $this->createMock(Rule::class);

        $this->ruleFactory->expects($this->any())
            ->method('create')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('load')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('getId')
            ->will($this->onConsecutiveCalls(1, 2));

        $conditions = $this->getMockBuilder(FilterableConditionInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isFilterable', 'getFilterGroups'])
            ->addMethods(['asArray'])
            ->getMockForAbstractClass();

        $conditions->expects($this->any())
            ->method('isFilterable')
            ->willReturn(true);

        $rule->expects($this->exactly(2))
            ->method('getConditions')
            ->willReturn($conditions);

        $filterGroupInterface = $this->getMockBuilder(FilterGroupInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(
                [
                    'getWeight',
                    'getFilterText',
                    'getFilterTextGeneratorClass',
                    'getFilterTextGeneratorArguments'
                ]
            )
            ->onlyMethods(['getFilters', 'setFilters'])
            ->getMockForAbstractClass();

        $filterGroupInterface->expects($this->any())
            ->method('getWeight')
            ->willReturn(3);

        $filterGroupInterface->expects($this->any())
            ->method('getFilterText')
            ->willReturn('class');

        $filterGroupInterface->expects($this->any())
            ->method('getFilterTextGeneratorClass')
            ->willReturn(4);

        $filterableConditionInterface = $this->getMockBuilder(FilterableConditionInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isFilterable', 'getFilterGroups'])
            ->addMethods(['getFilters'])
            ->getMockForAbstractClass();

        $filterableConditionInterface->expects($this->any())
            ->method('getFilters')
            ->willReturn([$filterGroupInterface]);

        $conditions->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn([$filterableConditionInterface, $filterableConditionInterface]);

        $expectArray1 = [
            [
                'rule_id' => 1,
                'group_id' => 1,
                'weight' => 3,
                Filter::KEY_FILTER_TEXT => 'class',
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => 4,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
            ],
            [
                'rule_id' => 1,
                'group_id' => 2,
                'weight' => 3,
                Filter::KEY_FILTER_TEXT => 'class',
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => 4,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
            ]
        ];

        $expectArray2 = [
            [
                'rule_id' => 2,
                'group_id' => 1,
                'weight' => 3,
                Filter::KEY_FILTER_TEXT => 'class',
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => 4,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
            ],
            [
                'rule_id' => 2,
                'group_id' => 2,
                'weight' => 3,
                Filter::KEY_FILTER_TEXT => 'class',
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => 4,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
            ]
        ];

        $this->filterResourceModel->expects($this->any())
            ->method('insertFilters')
            ->withConsecutive([$expectArray1], [$expectArray2]);

        $this->model->execute($rows);
    }

    /**
     * test Execute
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithInstanceNotFilterable()
    {
        $rows = [1, 2, 3];

        $className = Rule::class;
        $rule = $this->createMock($className);

        $this->ruleFactory->expects($this->any())
            ->method('create')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('load')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $conditions = $this->getMockBuilder(FilterableConditionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['asArray'])
            ->onlyMethods(['isFilterable', 'getFilterGroups'])
            ->getMockForAbstractClass();

        $conditions->expects($this->any())
            ->method('isFilterable')
            ->willReturn(false);

        $rule->expects($this->exactly(3))
            ->method('getConditions')
            ->willReturn($conditions);

        $filterGroupInterface = $this->getMockBuilder(FilterGroupInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(
                [
                    'getWeight',
                    'getFilterText',
                    'getFilterTextGeneratorClass',
                    'getFilterTextGeneratorArguments'
                ]
            )
            ->onlyMethods(['getFilters', 'setFilters'])
            ->getMockForAbstractClass();

        $filterGroupInterface->expects($this->any())
            ->method('getWeight')
            ->willReturn(3);

        $filterGroupInterface->expects($this->any())
            ->method('getFilterText')
            ->willReturn('class');

        $filterGroupInterface->expects($this->any())
            ->method('getFilterTextGeneratorClass')
            ->willReturn(4);

        $filterableConditionInterface = $this->getMockBuilder(FilterableConditionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getFilters'])
            ->onlyMethods(['getFilterGroups', 'isFilterable'])
            ->getMockForAbstractClass();

        $filterableConditionInterface->expects($this->any())
            ->method('getFilters')
            ->willReturn([$filterGroupInterface]);

        $conditions->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn([$filterableConditionInterface, $filterableConditionInterface]);

        $expectArray = [
            'rule_id' => 1,
            'group_id' => 1,
            'weight' => 1,
            Filter::KEY_FILTER_TEXT => 'true',
            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
        ];

        $this->filterResourceModel->expects($this->exactly(3))
            ->method('insertFilters')
            ->with($expectArray);

        $this->model->execute($rows);
    }
}
