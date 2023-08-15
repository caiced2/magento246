<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedRule\Test\Unit\Helper;

use Magento\AdvancedRule\Helper\CombineCondition;
use Magento\AdvancedRule\Helper\Filter as FilterHelper;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CombineConditionTest extends TestCase
{
    /**
     * @var CombineCondition
     */
    private $combineConditionHelper;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var FilterHelper|MockObject
     */
    private $filterHelperMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->filterHelperMock = $this->getMockBuilder(FilterHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->combineConditionHelper = $this->objectManager->getObject(
            CombineCondition::class,
            [
                'filterHelper' => $this->filterHelperMock,
            ]
        );
    }

    /**
     * @param array $conditions
     * @param bool $expected
     * @dataProvider hasFilterableConditionDataProvider
     */
    public function testHasFilterableCondition(array $conditions, $expected)
    {
        $result = $this->combineConditionHelper->hasFilterableCondition($conditions);
        $this->assertEquals($expected, $result);
    }

    public function hasFilterableConditionDataProvider()
    {
        $data = [
            'three_conditions_one_filterable' => [
                'conditions' => [
                    new DataObject(),
                    $this->getFilterableConditionMock(true),
                    $this->getFilterableConditionMock(false),
                ],
                'expected' => true,
            ],
            'three_conditions_no_filterable' => [
                'conditions' => [
                    new DataObject(),
                    $this->getFilterableConditionMock(false),
                    $this->getFilterableConditionMock(false),
                ],
                'expected' => false,
            ],
        ];
        return $data;
    }

    /**
     * @param array $conditions
     * @param bool $expected
     * @dataProvider hasNonFilterableConditionDataProvider
     */
    public function testHasNonFilterableCondition($conditions, $expected)
    {
        $result = $this->combineConditionHelper->hasNonFilterableCondition($conditions);
        $this->assertEquals($expected, $result);
    }

    public function hasNonFilterableConditionDataProvider()
    {
        $data = [
            'two_conditions_one_filterable' => [
                'conditions' => [
                    new DataObject(),
                    $this->getFilterableConditionMock(true),
                ],
                'expected' => true,
            ],
            'non_filterable_condition' => [
                'conditions' => [
                    $this->getFilterableConditionMock(false),
                    $this->getFilterableConditionMock(true),
                ],
                'expected' => true,
            ],
            'three_conditions_all_filterable' => [
                'conditions' => [
                    $this->getFilterableConditionMock(true),
                    $this->getFilterableConditionMock(true),
                ],
                'expected' => false,
            ],
        ];
        return $data;
    }

    public function testLogicalAndConditions()
    {
        $conditions = [];

        $filterGroups1 = [$this->getFilterGroupMock()];
        $conditions[] = $this->getFilterableConditionMock(true, $filterGroups1);

        $filterGroups2 = [$this->getFilterGroupMock()];
        $conditions[] = $this->getFilterableConditionMock(true, $filterGroups2);

        $returnedFilterGroups = [$this->getFilterGroupMock()];
        $this->filterHelperMock->expects($this->once())
            ->method('logicalAndFilterGroupArray')
            ->with($filterGroups1, $filterGroups2)
            ->willReturn($returnedFilterGroups);
        $result = $this->combineConditionHelper->logicalAndConditions($conditions);
        $this->assertEquals($returnedFilterGroups, $result);
    }

    public function testLogicalOrConditions()
    {
        $conditions = [];

        $filterGroups1 = [$this->getFilterGroupMock()];
        $conditions[] = $this->getFilterableConditionMock(true, $filterGroups1);

        $filterGroups2 = [$this->getFilterGroupMock()];
        $conditions[] = $this->getFilterableConditionMock(true, $filterGroups2);

        $returnedFilterGroups = array_merge($filterGroups1, $filterGroups2);

        $result = $this->combineConditionHelper->logicalOrConditions($conditions);
        $this->assertEquals($returnedFilterGroups, $result);
    }

    protected function getFilterGroupMock()
    {
        return $this->getMockForAbstractClass(FilterGroupInterface::class);
    }

    protected function getFilterableConditionMock($isFilterable, $filterGroups = null)
    {
        $mock = $this->getMockForAbstractClass(FilterableConditionInterface::class);

        $mock->expects($this->any())
            ->method('isFilterable')
            ->willReturn($isFilterable);

        $mock->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn($filterGroups);

        return $mock;
    }
}
