<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Layout;
use Magento\Framework\View\LayoutFactory;
use Magento\Support\Block\Adminhtml\Report\Export\Html;
use Magento\Support\Controller\Adminhtml\Report\Download;
use Magento\Support\Model\Report;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DownloadTest extends TestCase
{
    /**
     * @var Download
     */
    protected $downloadAction;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var LayoutFactory|MockObject
     */
    protected $layoutFactory;

    /**
     * @var FileFactory|MockObject
     */
    protected $fileFactory;

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
     * @var \Magento\Support\Model\ReportFactory|MockObject
     */
    protected $reportFactoryMock;

    /**
     * @var Report|MockObject
     */
    protected $reportMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->layoutFactory = $this->createMock(LayoutFactory::class);
        $this->fileFactory = $this->createMock(FileFactory::class);

        $this->reportMock = $this->createMock(Report::class);
        $this->reportFactoryMock = $this->createPartialMock(\Magento\Support\Model\ReportFactory::class, ['create']);
        $this->reportFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->reportMock);

        $this->resultRedirectMock = $this->createMock(Redirect::class);
        $this->resultRedirectMock->expects($this->any())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirectMock);

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

        $this->downloadAction = $this->objectManagerHelper->getObject(
            Download::class,
            [
                'context' => $this->contextMock,
                'reportFactory' => $this->reportFactoryMock,
                'layoutFactory' => $this->layoutFactory,
                'fileFactory' => $this->fileFactory
            ]
        );
    }

    /**
     * @param int $id
     * @return void
     */
    protected function setIdReport($id)
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($id);
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
    }

    /**
     * @return void
     */
    public function testExecuteMainFlow()
    {
        $id = 1;
        $fileName = 'report.html';
        $content = 'Some text';

        $this->setIdReport($id);
        $this->reportMock->expects($this->once())
            ->method('load')
            ->with($id)
            ->willReturnSelf();
        $this->reportMock->expects($this->once())
            ->method('getFileNameForReportDownload')
            ->willReturn($fileName);

        /** @var AbstractBlock|MockObject $block */
        $block = $this->createMock(AbstractBlock::class);
        $block->expects($this->once())
            ->method('setData')
            ->with(['report' => $this->reportMock])
            ->willReturnSelf();
        $block->expects($this->once())
            ->method('toHtml')
            ->willReturn($content);

        /** @var Layout|MockObject $layout */
        $layout = $this->createMock(Layout::class);
        $layout->expects($this->once())
            ->method('createBlock')
            ->with(Html::class, 'report.export.html')
            ->willReturn($block);

        $this->layoutFactory->expects($this->once())
            ->method('create')
            ->willReturn($layout);

        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $this->fileFactory->expects($this->once())
            ->method('create')
            ->with(
                $fileName,
                $content,
                DirectoryList::VAR_DIR
            )->willReturn($response);

        $this->assertInstanceOf(ResponseInterface::class, $this->downloadAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithoutReport()
    {
        $id = 0;
        $this->setIdReport($id);
        $this->reportMock->expects($this->never())->method('load');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Requested system report no longer exists.'))
            ->willReturnSelf();

        $this->assertSame($this->resultRedirectMock, $this->downloadAction->execute());
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
        $this->assertSame($this->resultRedirectMock, $this->downloadAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithException()
    {
        $e = new \Exception();
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willThrowException($e);
        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with($e, __('Unable to read system report data to display.'))
            ->willReturnSelf();
        $this->assertSame($this->resultRedirectMock, $this->downloadAction->execute());
    }
}
