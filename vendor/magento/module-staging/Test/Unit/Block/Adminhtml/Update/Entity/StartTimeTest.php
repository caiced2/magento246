<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update\Entity;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\StartTime;
use Magento\Staging\Model\VersionHistoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StartTimeTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $contextMock;

    /**
     * @var MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var MockObject
     */
    private $versionHistoryMock;

    /**
     * @var StartTime
     */
    private $model;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $uiComponentMock = $this->getMockBuilder(UiComponentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $uiComponentMock->expects($this->any())
            ->method('getData')
            ->withAnyParameters()
            ->willReturn(['extends' => ['test']]);

        $uiComponentFactoryMock = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uiComponentFactoryMock->expects($this->any())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($uiComponentMock);

        $this->updateRepositoryMock = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->versionHistoryMock = $this->getMockBuilder(VersionHistoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            StartTime::class,
            [
                'context' => $this->contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'updateRepository' => $this->updateRepositoryMock,
                'versionHistory' => $this->versionHistoryMock
            ]
        );
    }

    public function testPrepareActiveCompany()
    {
        $id = 123;
        $data['config']['formElement'] = 'testStartTime';
        $this->model->setData($data);

        $processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getProcessor')
            ->willReturn($processorMock);
        $this->contextMock->expects($this->any())
            ->method('getRequestParam')
            ->willReturn($id);
        $dataProvider = DataProviderInterface::class;
        $dataProviderMock = $this->getMockBuilder($dataProvider)
            ->disableOriginalConstructor()
            ->getMock();
        $dataProviderMock->expects($this->once())
            ->method('getRequestFieldName')
            ->willReturn('id');
        $this->contextMock->expects($this->any())
            ->method('getDataProvider')
            ->willReturn($dataProviderMock);

        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn($id);

        $this->model->prepare();
        $data = $this->model->getData();
        $this->assertEquals(1, $data['config']['disabled']);
    }

    public function testPrepareUpcomingCompany()
    {
        $id = 123;
        $data['config']['formElement'] = 'testStartTime';
        $this->model->setData($data);

        $processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getProcessor')
            ->willReturn($processorMock);
        $this->contextMock->expects($this->any())
            ->method('getRequestParam')
            ->willReturn($id);
        $dataProvider = DataProviderInterface::class;
        $dataProviderMock = $this->getMockBuilder($dataProvider)
            ->disableOriginalConstructor()
            ->getMock();
        $dataProviderMock->expects($this->once())
            ->method('getRequestFieldName')
            ->willReturn('id');
        $this->contextMock->expects($this->any())
            ->method('getDataProvider')
            ->willReturn($dataProviderMock);

        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn($id + 1);

        $this->model->prepare();
        $data = $this->model->getData();
        $this->assertNotContains('disabled', $data['config']);
    }
}
