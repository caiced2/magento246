<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\ConcreteCondition;

use Magento\CustomerSegment\Model\Segment\Condition\ConcreteCondition\Factory;
use Magento\CustomerSegment\Model\Segment\Condition\Segment;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var Segment|MockObject
     */
    protected $segmentCondition;

    /**
     * @var MockObject
     */
    protected $concreteCondition;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerInterface;

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

        $this->segmentCondition = $this->getMockBuilder(Segment::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOperator'])
            ->onlyMethods(['getValue'])
            ->getMock();

        $className = Factory::CONCRETE_CONDITION_CLASS;
        $this->concreteCondition = $this->createMock($className);

        $className = ObjectManagerInterface::class;
        $this->objectManagerInterface = $this->createMock($className);

        $className = Factory::class;
        $this->factory = $this->objectManager->getObject(
            $className,
            [
                'objectManager' => $this->objectManagerInterface,
            ]
        );
    }

    /**
     * test create
     */
    public function testCreate()
    {
        $this->segmentCondition->expects($this->once())
            ->method('getOperator')
            ->willReturn('==');
        $this->segmentCondition->expects($this->once())
            ->method('getValue')
            ->willReturn('1');

        $this->objectManagerInterface->expects($this->once())
            ->method('create')
            ->with(Factory::CONCRETE_CONDITION_CLASS)
            ->willReturn($this->concreteCondition);

        $result = $this->factory->create($this->segmentCondition);
        $this->assertEquals($this->concreteCondition, $result);
    }
}
