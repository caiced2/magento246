<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\Product;

use Magento\AdvancedRule\Helper\CombineCondition;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedSalesRule\Model\Rule\Condition\Product\Subselect;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\Context;
use Magento\SalesRule\Model\Rule\Condition\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubselectTest extends TestCase
{
    /**
     * @var Subselect
     */
    protected $model;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var Product|MockObject
     */
    protected $ruleConditionProduct;

    /**
     * @var CombineCondition|MockObject
     */
    protected $conditionHelper;

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

        $className = Context::class;
        $this->context = $this->createMock($className);

        $className = Product::class;
        $this->ruleConditionProduct = $this->createMock($className);

        $className = CombineCondition::class;
        $this->conditionHelper = $this->createMock($className);

        $this->model = $this->objectManager->getObject(
            Subselect::class,
            [
                'context' => $this->context,
                'ruleConditionProduct' => $this->ruleConditionProduct,
                'conditionHelper' => $this->conditionHelper,
            ]
        );
    }

    /**
     * test IsFilterable
     * @param float $valueParsed
     * @param string $operator
     * @param string $aggregator
     * @param string $expect
     * @param string $return
     * @param string $result
     * @dataProvider isFilterableDataProvider
     */
    public function testIsFilterable($valueParsed, $operator, $aggregator, $expect, $return, $result)
    {
        $this->model->setValueParsed($valueParsed);
        $this->model->setOperator($operator);
        $this->model->setAggregator($aggregator);

        $this->conditionHelper->expects($this->any())
            ->method($expect)
            ->willReturn($return);

        $this->assertEquals($result, $this->model->isFilterable());
    }

    /**
     * @return array
     */
    public function isFilterableDataProvider()
    {
        return [
            'greater_all_has_filterable_cond_non_filterable' => [1, '>', 'all', 'hasFilterableCondition', true, true],
            'greater_eq_all_has_filterable_cond_filterable' => [1, '>=', 'all', 'hasFilterableCondition', true, true],
            'less_all_has_filterable_cond_non_filterable' => [1, '<', 'all', 'hasFilterableCondition', true, false],
            'all_has_filterable_cond_non_filterable' => [0, '>=', 'all', 'hasFilterableCondition', true, false],
            'all_has_non_filterable_cond_non_filterable' => [1, '>', 'all', 'hasFilterableCondition', false, false],
            'none_has_non_filterable_cond_filterable' =>  [1, '>', 'none', 'hasNonFilterableCondition', false, true],
            'none_has_non_filterable_cond_non_filterable' => [1, '>', 'none', 'hasNonFilterableCondition', true, false],
        ];
    }

    /**
     * test GetFilterGroups
     * @param string $aggregator
     * @param string $expect
     * @dataProvider getFilterGroupsDataProvider
     */
    public function testGetFilterGroups($aggregator, $expect)
    {
        $className = FilterableConditionInterface::class;
        $interface =$this->createMock($className);

        $this->model->setAggregator($aggregator);

        $this->conditionHelper->expects($this->any())
            ->method($expect)
            ->willReturn($interface);

        $this->assertSame($interface, $this->model->getFilterGroups());
    }

    /**
     * @return array
     */
    public function getFilterGroupsDataProvider()
    {
        return [
            'all_logical_and_conditions' => ['all', 'logicalAndConditions'],
            'none_logical_or_conditions'=> ['none', 'logicalOrConditions']
        ];
    }
}
