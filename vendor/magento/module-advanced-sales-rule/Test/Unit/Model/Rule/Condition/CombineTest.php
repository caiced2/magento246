<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition;

use Magento\AdvancedRule\Helper\CombineCondition;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedSalesRule\Model\Rule\Condition\Combine;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\Context;
use Magento\SalesRule\Model\Rule\Condition\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CombineTest extends TestCase
{
    /**
     * @var Combine
     */
    protected $model;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $eventManager;

    /**
     * @var Address|MockObject
     */
    protected $conditionAddress;

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

        $className = ManagerInterface::class;
        $this->eventManager = $this->createMock($className);

        $className = Address::class;
        $this->conditionAddress = $this->createMock($className);

        $className = CombineCondition::class;
        $this->conditionHelper = $this->createMock($className);

        $this->model = $this->objectManager->getObject(
            Combine::class,
            [
                'context' => $this->context,
                'eventManager' => $this->eventManager,
                'conditionAddress' => $this->conditionAddress,
                'conditionHelper' => $this->conditionHelper,
            ]
        );
    }

    /**
     * test IsFilterable
     * @param bool $value
     * @param string $aggregator
     * @param string $expect
     * @param string $return
     * @param string $result
     * @dataProvider isFilterableDataProvider
     */
    public function testIsFilterable($value, $aggregator, $expect, $return, $result)
    {
        $this->model->setValue($value);
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
            'all_has_filterable_cond_filterable' => [true, 'all', 'hasFilterableCondition', true, true],
            'all_has_filterable_cond_non_filterable' => [false, 'all', 'hasFilterableCondition', false, false],
            'none_has_non_filterable_cond_filterable' => [true, 'none', 'hasNonFilterableCondition', false, true],
            'none_has_non_filterable_cond_non_filterable' => [false, 'none', 'hasNonFilterableCondition', false, false],
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
