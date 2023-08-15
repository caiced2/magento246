<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Support\Controller\Adminhtml\Report\View;
use Magento\Support\Model\DataFormatter;
use Magento\Support\Model\Report;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends TestCase
{
    /**
     * @var View
     */
    protected $viewAction;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\ReportFactory|MockObject
     */
    protected $reportFactoryMock;

    /**
     * @var Registry|MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var DataFormatter|MockObject
     */
    protected $dataFormatterMock;

    /**
     * @var Report|MockObject
     */
    protected $reportMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var Page|MockObject
     */
    protected $resultPageMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    protected $timeZoneMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->dataFormatterMock = $this->createMock(DataFormatter::class);

        $this->reportMock = $this->getMockBuilder(Report::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCreatedAt', 'load', 'getId'])
            ->getMock();
        $this->reportFactoryMock = $this->createPartialMock(\Magento\Support\Model\ReportFactory::class, ['create']);
        $this->reportFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->reportMock);

        $this->resultPageMock = $this->createMock(Page::class);
        $this->resultRedirectMock = $this->createMock(Redirect::class);
        $this->resultRedirectMock->expects($this->any())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnMap([
                [ResultFactory::TYPE_PAGE, [], $this->resultPageMock],
                [ResultFactory::TYPE_REDIRECT, [], $this->resultRedirectMock]
            ]);
        $this->timeZoneMock = $this->getMockForAbstractClass(TimezoneInterface::class);

        $this->contextMock = $this->createMock(Context::class);
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->viewAction = $this->objectManagerHelper->getObject(
            View::class,
            [
                'context' => $this->contextMock,
                'reportFactory' => $this->reportFactoryMock,
                'dataFormatter' => $this->dataFormatterMock,
                'timeZone' => $this->timeZoneMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecuteMainFlow()
    {
        $id = 1;
        $dateString = '01.01.1970 00:01';
        $sinceTimeString = '[1 minute ago]';
        $this->timeZoneMock->expects($this->once())->method('formatDateTime')->willReturn($dateString);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($id);

        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $this->reportMock->expects($this->once())
            ->method('load')
            ->with($id);
        $this->reportMock->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($dateString);

        $this->resultPageMock->expects($this->once())
            ->method('setActiveMenu')
            ->with('Magento_Support::support_report')
            ->willReturnSelf();

        $this->dataFormatterMock->expects($this->once())
            ->method('getSinceTimeString')
            ->with($dateString)
            ->willReturn($sinceTimeString);

        /** @var Title|MockObject $titleMock */
        $titleMock = $this->createMock(Title::class);
        $titleMock->expects($this->once())
            ->method('prepend')
            ->with($dateString . ' ' . $sinceTimeString);

        /** @var Config|MockObject $configMock */
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        $this->assertSame($this->resultPageMock, $this->viewAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithoutReport()
    {
        $id = 0;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($id);

        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $this->reportMock->expects($this->never())->method('load');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Requested system report no longer exists.'))
            ->willReturnSelf();

        $this->assertSame($this->resultRedirectMock, $this->viewAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $e = new LocalizedException(__('Test error'));
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willThrowException($e);
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with($e)
            ->willReturnSelf();
        $this->assertSame($this->resultRedirectMock, $this->viewAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithException()
    {
        $errorMsg = 'Test error';
        $exception = new \Exception($errorMsg);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willThrowException($exception);
        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with($exception, __('Unable to read system report data to display.'))
            ->willReturnSelf();
        $this->assertSame($this->resultRedirectMock, $this->viewAction->execute());
    }
}
