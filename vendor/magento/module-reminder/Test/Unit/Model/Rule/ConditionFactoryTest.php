<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Model\Rule;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reminder\Model\Rule\Condition\Cart\Amount;
use Magento\Reminder\Model\Rule\ConditionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConditionFactoryTest extends TestCase
{
    /**
     * @var ConditionFactory
     */
    private $model;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $objectManager;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);

        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->setMethods(['create', 'get', 'configure'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->model = $helper->getObject(
            ConditionFactory::class,
            ['objectManager' => $this->objectManager]
        );
    }

    public function testCreate()
    {
        $type = Amount::class;

        $amount = $this->getMockBuilder($type)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager->expects($this->once())->method('create')->willReturn($amount);

        $result = $this->model->create($type);

        $this->assertInstanceOf("\\$type", $result);
    }

    public function testCreateInvalidArgumentException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Condition type is unexpected');
        $type = 'someInvalidType';

        $this->model->create($type);
    }
}
