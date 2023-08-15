<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Controller\Adminhtml\Report\MassDelete;
use Magento\Support\Model\Report;
use Magento\Support\Model\ResourceModel\Report\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassDeleteTest extends TestCase
{
    /** @var MassDelete */
    protected $massDeleteController;

    /** @var ObjectManager */
    protected $objectManager;

    /** @var Context|MockObject */
    protected $contextMock;

    /** @var ResultFactory|MockObject */
    protected $resultFactory;

    /** @var Redirect|MockObject */
    protected $resultRedirect;

    /** @var ManagerInterface|MockObject */
    protected $messageManagerMock;

    /** @var RequestInterface|MockObject */
    protected $requestMock;

    /** @var \Magento\Framework\ObjectManager\ObjectManager|MockObject */
    protected $objectManagerMock;

    /** @var MockObject $pageMock */
    protected $reportCollectionMock;

    /** @var Report|MockObject */
    protected $modelMock;

    protected $reportId = '1';

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);

        $this->requestMock = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getParam']
        );

        $this->modelMock = $this->getMockBuilder(\Magento\Framework\ObjectManager\ObjectManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'delete'])
            ->getMock();

        $this->reportCollectionMock = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getAllIds'])
            ->getMock();

        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManager\ObjectManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->atLeastOnce())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirect);

        $this->contextMock = $this->createMock(Context::class);

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())->method('getMessageManager')->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())->method('getObjectManager')->willReturn($this->objectManagerMock);
        $this->contextMock->expects($this->once())->method('getResultFactory')->willReturn($this->resultFactory);

        $this->massDeleteController = $this->objectManager->getObject(
            MassDelete::class,
            [
                'context' => $this->contextMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testSelectedDelete()
    {
        $selected = ['1'];
        $count = 1;

        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['selected', null, $selected],
                ['excluded', null, null]
            ]
        );

        $this->objectManagerMock->expects($this->atLeastOnce())->method('get')->willReturnMap(
            [
                [Collection::class, $this->reportCollectionMock],
                [Report::class, $this->modelMock]
            ]
        );

        $this->reportCollectionMock->expects($this->once())->method('addFieldToFilter')->with(
            'report_id',
            ['in' => $selected]
        );
        $this->reportCollectionMock->expects($this->once())->method('getAllIds')->willReturn($selected);

        $this->modelMock->expects($this->once())->method('load')->with($this->reportId)->willReturnSelf();
        $this->modelMock->expects($this->once())->method('delete')->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('A total of %1 record(s) have been deleted.', $count))
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->never())
            ->method('addError');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->massDeleteController->execute());
    }

    /**
     * @return void
     */
    public function testExcludedDelete()
    {
        $excluded = ['2'];
        $selected = ['1', '3'];
        $count = 2;

        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['selected', null, null],
                ['excluded', null, $excluded]
            ]
        );

        $this->objectManagerMock->expects($this->atLeastOnce())->method('get')->willReturnMap(
            [
                [Collection::class, $this->reportCollectionMock],
                [Report::class, $this->modelMock]
            ]
        );

        $this->reportCollectionMock->expects($this->once())->method('addFieldToFilter')->with(
            'report_id',
            ['nin' => $excluded]
        );
        $this->reportCollectionMock->expects($this->once())->method('getAllIds')->willReturn($selected);

        $this->modelMock->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $this->modelMock->expects($this->atLeastOnce())->method('delete')->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('A total of %1 record(s) have been deleted.', $count))
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->never())
            ->method('addError');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->massDeleteController->execute());
    }

    /**
     * @return void
     */
    public function testDeleteAll()
    {
        $ids = ['1', '2', '3'];
        $count = 3;

        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['selected', null, null],
                ['excluded', null, 'false']
            ]
        );

        $this->objectManagerMock->expects($this->atLeastOnce())->method('get')->willReturnMap(
            [
                [Collection::class, $this->reportCollectionMock],
                [Report::class, $this->modelMock]
            ]
        );

        $this->reportCollectionMock->expects($this->once())->method('getAllIds')->willReturn($ids);

        $this->modelMock->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $this->modelMock->expects($this->atLeastOnce())->method('delete')->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('A total of %1 record(s) have been deleted.', $count))
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->never())
            ->method('addError');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->massDeleteController->execute());
    }

    /**
     * @return void
     */
    public function testNoItemsSelected()
    {
        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['selected', null, null],
                ['excluded', null, null]
            ]
        );

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('An item needs to be selected. Select and try again.'))
            ->willReturnSelf();

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->massDeleteController->execute());
    }

    /**
     * @return void
     */
    public function testExecuteThrowsException()
    {
        $exception = new \Exception(
            'An error occurred during mass deletion of system reports. Please review log and try again.'
        );

        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['selected', null, null],
                ['excluded', null, 'false']
            ]
        );

        $this->objectManagerMock->expects($this->atLeastOnce())->method('get')->willThrowException($exception);

        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with(
                $exception,
                'An error occurred during mass deletion of system reports. Please review log and try again.'
            )->willReturnSelf();
        $this->messageManagerMock->expects($this->never())->method('addSuccess');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->massDeleteController->execute());
    }
}
