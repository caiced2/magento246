<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model;

use Magento\CustomerSegment\Model\ConditionFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;
use PHPUnit\Framework\TestCase;

class ConditionFactoryTest extends TestCase
{
    /**
     * @var ConditionFactory
     */
    protected $model;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var AbstractCondition
     */
    protected $abstractCondition;

    /**
     * @var Context
     */
    protected $context;

    protected function setUp(): void
    {
        $this->objectManager = $this->getMockForAbstractClass(ObjectManagerInterface::class);

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractCondition = $this->getMockForAbstractClass(
            AbstractCondition::class,
            [$this->context]
        );

        $this->model = new ConditionFactory(
            $this->objectManager
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->model,
            $this->objectManager,
            $this->abstractCondition,
            $this->context
        );
    }

    public function testCreate()
    {
        $className = 'TestClass';
        $classNamePrefix = 'Magento\CustomerSegment\Model\Segment\Condition\\';

        $this->objectManager
            ->expects($this->once())
            ->method('create')
            ->with($classNamePrefix . $className)
            ->willReturn($this->abstractCondition);

        $result = $this->model->create($classNamePrefix . $className);

        $this->assertInstanceOf(AbstractCondition::class, $result);
    }

    public function testCreateWithError()
    {
        $className = 'TestClass';
        $classNamePrefix = 'Magento\CustomerSegment\Model\Segment\Condition\\';

        $this->objectManager
            ->expects($this->once())
            ->method('create')
            ->with($classNamePrefix . $className)
            ->willReturn(new \StdClass());

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            $classNamePrefix . $className . ' doesn\'t extends \Magento\Rule\Model\Condition\AbstractCondition'
        );

        $this->model->create($className);
    }
}
