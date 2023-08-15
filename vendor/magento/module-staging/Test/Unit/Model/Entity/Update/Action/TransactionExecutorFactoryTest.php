<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action;

use Magento\Framework\ObjectManagerInterface;
use Magento\Staging\Model\Entity\Update\Action\TransactionExecutor;
use Magento\Staging\Model\Entity\Update\Action\TransactionExecutorFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransactionExecutorFactoryTest extends TestCase
{
    /** @var MockObject */
    private $objectManager;

    /** @var string */
    private $instanceName = TransactionExecutor::class;

    /**
     * @var TransactionExecutorFactory
     */
    protected $factory;

    protected function setUp(): void
    {
        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->factory = new TransactionExecutorFactory(
            $this->objectManager,
            $this->instanceName
        );
    }

    public function testCreate()
    {
        $executorMock = $this->getMockBuilder($this->instanceName)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager->expects($this->once())
            ->method('get')
            ->with($this->instanceName)
            ->willReturn($executorMock);
        $this->assertInstanceOf($this->instanceName, $this->factory->create());
    }
}
