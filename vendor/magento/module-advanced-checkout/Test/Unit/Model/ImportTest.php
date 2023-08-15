<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Model;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\AdvancedCheckout\Model\Import;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\Filesystem\Directory\WriteInterface as DirectoryWriterInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\AdvancedCheckout\Model\Import class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportTest extends TestCase
{
    /**
     * @var MockObject|Data
     */
    protected $checkoutDataMock;

    /**
     * @var MockObject|UploaderFactory
     */
    protected $factoryMock;

    /**
     * @var MockObject|Filesystem
     */
    protected $filesystemMock;

    /**
     * @var MockObject|Random
     */
    protected $randomMock;

    /**
     * @var MockObject|DirectoryWriterInterface
     */
    protected $writeDirectoryMock;

    /**
     * @var  MockObject|Uploader
     */
    protected $uploaderMock;

    /**
     * @var Import
     */
    protected $import;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->randomMock = $this->createMock(Random::class);
        $this->checkoutDataMock = $this->createMock(Data::class);
        $this->factoryMock = $this->createPartialMock(
            UploaderFactory::class,
            ['create']
        );
        $this->filesystemMock = $this->createMock(Filesystem::class);

        $this->writeDirectoryMock = $this->createMock(Write::class);
        $this->uploaderMock = $this->createMock(Uploader::class);

        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->import = $objectManagerHelper->getObject(
            Import::class,
            [
                'checkoutData' => $this->checkoutDataMock,
                'uploaderFactory' => $this->factoryMock,
                'filesystem' => $this->filesystemMock,
                'random' => $this->randomMock
            ]
        );
    }

    public function testUploadFile(): void
    {
        $this->prepareUploadFileData();
        $this->import->uploadFile();
    }

    public function testUploadFileWhenExtensionIsNotAllowed(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Please upload the file in .csv format.');
        $allowedExtension = 'csv';
        $this->configureFactoryMock($allowedExtension);
        $this->uploaderMock
            ->expects($this->once())
            ->method('checkAllowedExtension')
            ->with($allowedExtension)
            ->willReturn(false);
        $this->writeDirectoryMock
            ->expects($this->never())
            ->method('getAbsolutePath');
        $this->import->uploadFile();
    }

    public function testUploadFileWhenImposibleSaveAbsolutePath(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->filesystemMock
            ->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn($this->writeDirectoryMock);
        $allowedExtension = 'csv';
        $absolutePath = 'path/path2';
        $this->configureFactoryMock($allowedExtension);
        $this->uploaderMock
            ->expects($this->once())
            ->method('checkAllowedExtension')
            ->with($allowedExtension)
            ->willReturn(true);
        $this->writeDirectoryMock
            ->expects($this->once())
            ->method('getAbsolutePath')
            ->with('import_sku/')
            ->willReturn($absolutePath);
        $this->uploaderMock
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception());
        $this->writeDirectoryMock
            ->expects($this->never())
            ->method('getRelativePath');
        $this->checkoutDataMock->expects($this->once())
            ->method('getFileGeneralErrorText')
            ->willReturn(__('Some Error'));
        $this->import->uploadFile();
    }

    public function testGetDataFromCsvWhenFileNotExist(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->checkoutDataMock->expects($this->once())
            ->method('getFileGeneralErrorText')
            ->willReturn(__('Some Error'));
        $this->import->getDataFromCsv();
    }

    public function testGetDataFromCsv(): void
    {
        $colNames = ['sku', 'qty'];
        $currentRow = [
            0 => 'ProductSku',
            1 => 3
        ];
        $expectedCsvData = [
            ['qty' => 3,
                'sku' => 'ProductSku'
            ]
        ];
        $fileHandlerMock = $this->createMock(\Magento\Framework\Filesystem\File\WriteInterface::class);
        $this->writeDirectoryMock
            ->expects($this->once())
            ->method('isExist')
            ->with('file_name.csv')
            ->willReturn(true);
        $this->writeDirectoryMock
            ->expects($this->once())
            ->method('openFile')
            ->with('file_name.csv', 'r')
            ->willReturn($fileHandlerMock);
        $fileHandlerMock
            ->method('readCsv')
            ->willReturnOnConsecutiveCalls($colNames, $currentRow, false);
        $fileHandlerMock->expects($this->once())->method('close');
        $this->prepareUploadFileData();
        $this->import->uploadFile();
        $this->assertEquals($expectedCsvData, $this->import->getDataFromCsv());
    }

    public function testGetDataFromCsvFromInvalidFile(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('The file is corrupt and can\'t be used.');
        $colNames = ['one', 'qty'];
        $fileHandlerMock = $this->createMock(\Magento\Framework\Filesystem\File\WriteInterface::class);
        $this->writeDirectoryMock
            ->expects($this->once())
            ->method('isExist')
            ->with('file_name.csv')
            ->willReturn(true);
        $this->writeDirectoryMock
            ->expects($this->once())
            ->method('openFile')
            ->with('file_name.csv', 'r')
            ->willReturn($fileHandlerMock);
        $this->checkoutDataMock->expects($this->once())
            ->method('getSkuEmptyDataMessageText')
            ->willReturn(__('Some Error'));
        $fileHandlerMock
            ->method('readCsv')
            ->willReturn($colNames);
        $this->prepareUploadFileData();
        $this->import->uploadFile();
        $this->import->getDataFromCsv();
    }

    public function testGetDataFromCsvWhenFileCorrupt(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('The file is corrupt and can\'t be used.');
        $this->writeDirectoryMock
            ->expects($this->once())
            ->method('isExist')
            ->with('file_name.csv')
            ->willReturn(true);
        $this->writeDirectoryMock
            ->expects($this->once())
            ->method('openFile')
            ->with('file_name.csv', 'r')
            ->willThrowException(new \Exception());
        $this->prepareUploadFileData();
        $this->import->uploadFile();
        $this->import->getDataFromCsv();
    }

    public function testDestruct(): void
    {
        $this->writeDirectoryMock->expects($this->once())->method('delete')->with('file_name.csv');
        $this->prepareUploadFileData();
        $this->import->uploadFile();
        $this->import->destruct();
    }

    public function testGetRowsWhenFileNotExist(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->checkoutDataMock->expects($this->once())
            ->method('getFileGeneralErrorText')
            ->willReturn(__('Some Error'));
        $this->prepareUploadFileData();
        $this->import->uploadFile();
        $this->import->getRows();
    }

    protected function prepareUploadFileData(): void
    {
        $this->filesystemMock
            ->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn($this->writeDirectoryMock);
        $allowedExtension = 'csv';
        $absolutePath = 'path/path2';
        $newFileString = 'filename_string';
        $result = [
            'name' => 'file_name.csv',
            'path' => $absolutePath,
            'file' => $newFileString . 'csv'
        ];
        $this->configureFactoryMock($allowedExtension);
        $this->uploaderMock
            ->expects($this->once())
            ->method('checkAllowedExtension')
            ->with($allowedExtension)
            ->willReturn(true);
        $this->writeDirectoryMock
            ->expects($this->once())
            ->method('getAbsolutePath')
            ->with('import_sku/')
            ->willReturn($absolutePath);
        $this->uploaderMock
            ->expects($this->once())
            ->method('save')
            ->with($absolutePath)
            ->willReturnCallback(
                function ($absolutePath, $newFileName) use ($newFileString, $result) {
                    self::assertEquals($newFileString . '.csv', $newFileName);
                    return $result;
                }
            );
        $this->writeDirectoryMock
            ->expects($this->once())
            ->method('getRelativePath')
            ->with($result['path'] . $result['file'])
            ->willReturn('file_name.csv');
        $this->randomMock
            ->expects($this->once())
            ->method('getRandomString')
            ->willReturn($newFileString);
    }

    /**
     * Prepare factory mock with uploader mock
     *
     * @param string $allowedExtension
     *
     * @return void
     */
    private function configureFactoryMock(string $allowedExtension = 'csv'): void
    {
        $allowedExtension = 'csv';
        $this->factoryMock
            ->expects($this->once())
            ->method('create')
            ->with(['fileId' => 'sku_file'])
            ->willReturn($this->uploaderMock);
        $this->uploaderMock->expects($this->once())->method('setAllowedExtensions')->with(['csv']);
        $this->uploaderMock->expects($this->once())->method('skipDbProcessing')->with(true);
        $this->uploaderMock->expects($this->once())->method('getFileExtension')->willReturn($allowedExtension);
    }

    public function testUploadFileFail(): void
    {
        $errorMessage = __('You cannot upload this file.');
        $writeMock =  $this->getMockBuilder(Write::class)
            ->onlyMethods(['getAbsolutePath'])
            ->disableOriginalConstructor()
            ->getMock();
        $writeMock->expects($this->once())
            ->method('getAbsolutePath')
            ->willReturn('test_absolute_path');
        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryWrite')->willReturn($writeMock);
        $this->uploaderMock->expects($this->once())
            ->method('checkAllowedExtension')
            ->willReturn(true);
        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with(['fileId' => 'sku_file'])
            ->willReturn($this->uploaderMock);
        $this->checkoutDataMock->method('getFileGeneralErrorText')->willReturn($errorMessage);
        $this->uploaderMock->expects($this->once())->method('save')->willReturn(false);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((string) $errorMessage);
        $this->import->uploadFile();
    }
}
