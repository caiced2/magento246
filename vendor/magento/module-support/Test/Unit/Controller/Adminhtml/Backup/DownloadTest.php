<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Controller\Adminhtml\Backup;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Controller\Adminhtml\Backup\Download;
use Magento\Support\Helper\Shell;
use Magento\Support\Model\Backup;
use Magento\Support\Model\Backup\AbstractItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DownloadTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Download
     */
    protected $downloadAction;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var Shell|MockObject
     */
    protected $shellHelperMock;

    /**
     * @var \Magento\Support\Model\BackupFactory|MockObject ;
     */
    protected $backupFactoryMock;

    /**
     * @var Backup|MockObject
     */
    protected $backupMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var FileFactory|MockObject
     */
    protected $fileFactoryMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var Filesystem|MockObject
     */
    protected $filesystemMock;

    /**
     * @var Filesystem\Directory\ReadInterface|MockObject
     */
    protected $readMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->shellHelperMock = $this->createMock(Shell::class);
        $this->backupMock = $this->createMock(Backup::class);
        $this->backupFactoryMock = $this->createPartialMock(\Magento\Support\Model\BackupFactory::class, ['create']);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->fileFactoryMock = $this->createMock(FileFactory::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->readMock = $this->getMockForAbstractClass(ReadInterface::class);

        $backupId = 1;
        $backupType = 1;
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['backup_id', 0, $backupId],
                ['type', 0, $backupType]
            ]);
        $this->backupMock->expects($this->once())
            ->method('load')
            ->with($backupId)
            ->willReturnSelf();

        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            [
                'request' => $this->requestMock,
                'messageManager' => $this->messageManagerMock,
                'resultFactory' => $this->resultFactoryMock
            ]
        );
        $this->downloadAction = $this->objectManagerHelper->getObject(
            Download::class,
            [
                'context' => $this->context,
                'shellHelper' => $this->shellHelperMock,
                'backupFactory' => $this->backupFactoryMock,
                'fileFactory' => $this->fileFactoryMock,
                'filesystem' => $this->filesystemMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $filePath = 'some_path';
        $backupName = 'someName';

        $this->backupMock->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $this->getAbstractItem($backupName),
                $this->getAbstractItem($backupName, 1),
            ]);
        $this->backupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->backupMock);

        $this->shellHelperMock->expects($this->once())
            ->method('getFilePath')
            ->with($backupName)
            ->willReturn($filePath);
        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryRead')
            ->with(DirectoryList::ROOT)
            ->willReturn($this->readMock);
        $this->readMock->expects($this->once())
            ->method('isExist')
            ->with($filePath)
            ->willReturn(true);

        $this->fileFactoryMock->expects($this->once())
            ->method('create')
            ->with($backupName, ['value' => $filePath, 'type'  => 'filename']);

        $this->downloadAction->execute();
    }

    /**
     * @return void
     */
    public function testExecuteWithoutItems()
    {
        $this->backupMock->expects($this->once())
            ->method('getItems')
            ->willReturn([]);
        $this->backupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->backupMock);

        $this->shellHelperMock->expects($this->never())
            ->method('getFilePath');
        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryRead')
            ->with(DirectoryList::ROOT)
            ->willReturn($this->readMock);
        $this->readMock->expects($this->once())
            ->method('isExist')
            ->with(null)
            ->willReturn(false);

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('File does not exist'))
            ->willReturnSelf();

        /** @var Redirect|MockObject $redirectMock */
        $redirectMock = $this->createMock(Redirect::class);
        $redirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($redirectMock);

        $this->assertSame($redirectMock, $this->downloadAction->execute());
    }

    /**
     * @param string $backupName
     * @param int $type
     * @return AbstractItem|MockObject
     */
    protected function getAbstractItem($backupName, $type = 0)
    {
        /** @var AbstractItem|MockObject $item */
        $item = $this->getMockBuilder(AbstractItem::class)
            ->setMethods(['getType', 'getName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())
            ->method('getType')
            ->willReturn($type);
        $item->expects($this->any())
            ->method('getName')
            ->willReturn($backupName);

        return $item;
    }
}
