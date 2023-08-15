<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Operation\Update;

use Magento\Framework\App\ObjectManager;
use Magento\Staging\Model\Operation\Update\PermanentUpdateProcessorPool;
use Magento\Staging\Model\Operation\Update\UpdateProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PermanentUpdateProcessorPoolTest extends TestCase
{
    /**
     * @var PermanentUpdateProcessorPool
     */
    private $model;

    /**
     * @var MockObject
     */
    private $objManagerMock;

    /**
     * @var MockObject
     */
    private $processorMock;

    protected function setUp(): void
    {
        $this->objManagerMock = $this->createMock(ObjectManager::class);
        $this->processorMock = $this->createMock(
            UpdateProcessorInterface::class
        );
        $processors = [
            'default' => 'DefaultProcessorMock',
            'NewEntityType' => 'NewEntityTypeMock'
        ];
        $this->model = new PermanentUpdateProcessorPool($this->objManagerMock, $processors);
    }

    public function testGetProcessor()
    {
        $this->objManagerMock
            ->expects($this->once())
            ->method('get')
            ->with('NewEntityTypeMock')
            ->willReturn($this->processorMock);
        $this->assertEquals($this->processorMock, $this->model->getProcessor('NewEntityType'));
    }

    public function testGetDefaultProcessor()
    {
        $this->objManagerMock
            ->expects($this->once())
            ->method('get')
            ->with('DefaultProcessorMock')
            ->willReturn($this->processorMock);
        $this->assertEquals($this->processorMock, $this->model->getProcessor('EntityWithoutProcessor'));
    }
}
