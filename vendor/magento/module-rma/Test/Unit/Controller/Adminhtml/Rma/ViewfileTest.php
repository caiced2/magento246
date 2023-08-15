<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryResolver;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Framework\Url\DecoderInterface;
use Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Magento\Rma\Controller\Adminhtml\Rma\Viewfile
 */
class ViewfileTest extends RmaTest
{
    /**
     * @var string
     */
    protected $name = 'Viewfile';

    /**
     * @var Raw|MockObject
     */
    protected $resultRawMock;

    /**
     * @var Filesystem|MockObject
     */
    protected $fileSystemMock;

    /**
     * @var Read|MockObject
     */
    protected $readDirectoryMock;

    /**
     * @var RawFactory|MockObject
     */
    protected $resultRawFactoryMock;

    /**
     * @var DecoderInterface|MockObject
     */
    protected $urlDecoderMock;

    /**
     * @var FileFactory|MockObject
     */
    protected $fileFactoryMock;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $fileResponseMock;

    /**
     * @var \Magento\Framework\Filesystem\File\Read|MockObject
     */
    protected $fileReadMock;

    /**
     * @var DirectoryResolver|MockObject
     */
    private $directoryResolverMock;

    protected function setUp(): void
    {
        $this->readDirectoryMock = $this->getMockBuilder(Read::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileSystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRawFactoryMock = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultRawMock = $this->getMockBuilder(Raw::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlDecoderMock = $this->getMockBuilder(DecoderInterface::class)
            ->getMock();
        $this->fileFactoryMock = $this->getMockBuilder(FileFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileResponseMock = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $this->fileReadMock = $this->getMockBuilder(\Magento\Framework\Filesystem\File\Read::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryResolverMock = $this->getMockBuilder(DirectoryResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['validatePath'])
            ->getMock();
        $this->fileSystemMock->expects($this->any())
            ->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->readDirectoryMock);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getConstructArguments()
    {
        $arguments = parent::getConstructArguments();
        $arguments['filesystem'] = $this->fileSystemMock;
        $arguments['resultRawFactory'] = $this->resultRawFactoryMock;
        $arguments['urlDecoder'] = $this->urlDecoderMock;
        $arguments['fileFactory'] = $this->fileFactoryMock;
        $arguments['directoryResolver'] = $this->directoryResolverMock;
        return $arguments;
    }

    /**
     * @covers \Magento\Rma\Controller\Adminhtml\Rma\Viewfile::execute
     * @throws NotFoundException
     */
    public function testExecuteNoParamsShouldThrowException()
    {
        $this->expectException('Magento\Framework\Exception\NotFoundException');
        $this->action->execute();
    }

    /**
     * @covers \Magento\Rma\Controller\Adminhtml\Rma\Viewfile::execute
     */
    public function testExecuteGetFile()
    {
        $file = 'file';
        $fileDecoded = 'file_decoded';
        $absolutePath = 'absolute/path';

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('file')
            ->willReturn($file);
        $this->urlDecoderMock->expects($this->any())
            ->method('decode')
            ->with($file)
            ->willReturn($fileDecoded);
        $this->readDirectoryMock->expects($this->any())
            ->method('isExist')
            ->willReturn(true);
        $this->readDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn($absolutePath);
        $this->fileFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $fileDecoded,
                ['type' => 'filename', 'value' => $absolutePath],
                DirectoryList::MEDIA
            )
            ->willReturn($this->fileResponseMock);
        $this->directoryResolverMock->expects($this->atLeastOnce())->method('validatePath')
            ->with($absolutePath, DirectoryList::MEDIA)
            ->willReturn(true);
        $this->action->execute();
    }

    public function testExecuteGetFileWithWrongPath()
    {
        $this->expectException('Magento\Framework\Exception\NotFoundException');
        $this->expectExceptionMessage('Page not found.');
        $file = 'file';
        $fileDecoded = 'file_decoded';
        $absolutePath = 'absolute/path';

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('file')
            ->willReturn($file);
        $this->urlDecoderMock->expects($this->any())
            ->method('decode')
            ->with($file)
            ->willReturn($fileDecoded);
        $this->readDirectoryMock->expects($this->any())
            ->method('isExist')
            ->willReturn(true);
        $this->readDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn($absolutePath);
        $this->fileFactoryMock->expects($this->never())->method('create');
        $this->directoryResolverMock->expects($this->atLeastOnce())->method('validatePath')
            ->with($absolutePath, DirectoryList::MEDIA)
            ->willReturn(false);
        $this->action->execute();
    }

    /**
     * @covers \Magento\Rma\Controller\Adminhtml\Rma\Viewfile::execute
     */
    public function testExecuteGetImage()
    {
        $file = 'file';
        $fileDecoded = 'file_decoded';
        $fileContents = 'file_contents';
        $fileStat = ['size' => 10, 'mtime' => 10];
        $absolutePath = 'absolute/path';

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['file', null, null],
                    ['image', null, $file]
                ]
            );
        $this->urlDecoderMock->expects($this->any())
            ->method('decode')
            ->with($file)
            ->willReturn($fileDecoded);
        $this->fileReadMock->expects($this->once())
            ->method('read')
            ->with($fileStat['size'])
            ->willReturn($fileContents);
        $this->readDirectoryMock->expects($this->any())
            ->method('isExist')
            ->willReturn(true);
        $this->readDirectoryMock->expects($this->any())
            ->method('openFile')
            ->willReturn($this->fileReadMock);
        $this->readDirectoryMock->expects($this->any())
            ->method('stat')
            ->with('rma_item/file_decoded')
            ->willReturn($fileStat);
        $this->resultRawMock->expects($this->any())
            ->method('setHttpResponseCode')
            ->with(200)
            ->willReturnSelf();
        $this->resultRawMock->expects($this->any())
            ->method('setHeader')
            ->willReturnSelf();
        $this->resultRawMock->expects($this->once())
            ->method('setContents')
            ->with($fileContents);
        $this->resultRawFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRawMock);
        $this->readDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn($absolutePath);
        $this->directoryResolverMock->expects($this->atLeastOnce())->method('validatePath')
            ->with($absolutePath, DirectoryList::MEDIA)
            ->willReturn(true);

        $this->assertSame($this->resultRawMock, $this->action->execute());
    }
}
