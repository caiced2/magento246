<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition;

use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\CustomerSegment\Model\Segment\Condition\ConcreteCondition\Factory;
use Magento\CustomerSegment\Model\Segment\Condition\Segment;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SegmentTest extends TestCase
{
    /**
     * @var Segment
     */
    protected $model;

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

        $className = Factory::class;
        $this->concreteConditionFactory = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $className = Segment::class;
        $this->model = $this->objectManager->getObject(
            $className,
            [
                'concreteConditionFactory' => $this->concreteConditionFactory,
            ]
        );
    }

    /**
     * test isFilterable
     */
    public function testIsFilterable()
    {
        $className = FilterableConditionInterface::class;
        $interface = $this->createMock($className);
        $interface->expects($this->any())
            ->method('isFilterable')
            ->willReturn(true);

        $this->concreteConditionFactory->expects($this->once())
            ->method('create')
            ->willReturn($interface);

        $this->assertTrue($this->model->isFilterable());
    }

    /**
     * test getFilterGroups
     */
    public function testGetFilterGroups()
    {
        $className = FilterGroupInterface::class;
        $filterGroupInterface = $this->createMock($className);

        $className = FilterableConditionInterface::class;
        $interface = $this->createMock($className);
        $interface->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn([$filterGroupInterface]);

        $this->concreteConditionFactory->expects($this->once())
            ->method('create')
            ->willReturn($interface);

        $this->assertEquals([$filterGroupInterface], $this->model->getFilterGroups());
    }
}
