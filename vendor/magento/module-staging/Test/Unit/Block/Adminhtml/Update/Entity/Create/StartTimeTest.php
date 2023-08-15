<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update\Entity\Create;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\Create\StartTime;
use Magento\Staging\Model\VersionHistoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for test start time field
 */
class StartTimeTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $contextMock;

    /**
     * @var MockObject
     */
    private $versionHistoryMock;

    /**
     * @var StartTime
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $uiComponentMock = $this->getMockBuilder(UiComponentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $uiComponentMock->method('getData')
            ->willReturn(['extends' => ['test']]);
        $uiComponentFactoryMock = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uiComponentFactoryMock->method('create')
            ->willReturn($uiComponentMock);
        $this->versionHistoryMock = $this->getMockBuilder(VersionHistoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            StartTime::class,
            [
                'context' => $this->contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'versionHistory' => $this->versionHistoryMock
            ]
        );
    }

    /**
     * Test for prepare active rule
     *
     * @return void
     */
    public function testPrepareActiveRule(): void
    {
        $updateId = 123;
        $data['config']['formElement'] = 'testStartTime';
        $this->model->setData($data);
        $processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getProcessor')
            ->willReturn($processorMock);
        $this->contextMock->method('getRequestParam')
            ->with('update_id')
            ->willReturn($updateId);
        $this->versionHistoryMock->method('getCurrentId')
            ->willReturn($updateId);
        $this->model->prepare();
        $data = $this->model->getData();

        $this->assertArrayHasKey('disabled', $data['config']);
    }

    /**
     * Test for prepare not active rule
     *
     * @return void
     */
    public function testPrepareNotActiveRule(): void
    {
        $updateId = 123;
        $data['config']['formElement'] = 'testStartTime';
        $this->model->setData($data);
        $processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->method('getProcessor')
            ->willReturn($processorMock);
        $this->contextMock->method('getRequestParam')
            ->with('update_id')
            ->willReturn($updateId);
        $this->versionHistoryMock->method('getCurrentId')
            ->willReturn(120);
        $this->model->prepare();
        $data = $this->model->getData();

        $this->assertArrayNotHasKey('disabled', $data['config']);
    }
}
