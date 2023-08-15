<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftWrapping\Model\Wrapping;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\System\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WrappingTest extends TestCase
{
    /** @var Wrapping */
    protected $wrapping;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Filesystem|MockObject */
    protected $filesystemMock;

    /** @var string */
    protected $testImagePath;

    /**
     * @var WriteInterface|MockObject
     */
    protected $mediaDirectoryMock;

    private function setupObjectManagerForCheckImageExist($return)
    {
        $objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $mockFileSystem = $this->createMock(Filesystem::class);
        $mockRead = $this->createMock(ReadInterface::class);
        $objectManagerMock->method($this->logicalOr('get', 'create'))->willReturn($mockFileSystem);
        $mockFileSystem->method('getDirectoryRead')->willReturn($mockRead);
        $mockRead->method('isExist')->willReturn($return);
        ObjectManager::setInstance($objectManagerMock);
    }

    protected function setUp(): void
    {
        $this->setupObjectManagerForCheckImageExist(false);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->mediaDirectoryMock = $this->getMockBuilder(WriteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])->getMockForAbstractClass();
        $this->filesystemMock->expects($this->once())->method('getDirectoryWrite')
            ->with(DirectoryList::MEDIA)->willReturn($this->mediaDirectoryMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->wrapping = $this->objectManagerHelper->getObject(
            Wrapping::class,
            [
                'filesystem' => $this->filesystemMock,
            ]
        );
        $this->testImagePath = __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'magento_image.jpg';
    }

    public function testAttachBinaryImageEmptyFileName()
    {
        $this->assertFalse($this->wrapping->attachBinaryImage('', ''));
    }

    /**
     * @dataProvider invalidBinaryImageDataProvider
     * @param $fileName
     * @param $imageContent
     * @param $exceptionMessage
     */
    public function testAttachBinaryImageExceptions($fileName, $imageContent, $exceptionMessage)
    {
        $this->expectException(InputException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->wrapping->attachBinaryImage($fileName, $imageContent);
    }

    /**
     * @return array
     */
    public function invalidBinaryImageDataProvider()
    {
        return [
            ['image.php', 'image content', 'The image extension "php" not allowed'],
            ['{}?*:<>.jpg', 'image content', 'Provided image name contains forbidden characters'],
            ['image.jpg', 'image content', 'The image content must be valid data'],
            ['image.jpg', '', 'The image content must be valid data'],
        ];
    }

    public function testAttachBinaryImageWriteFail()
    {
        $imageContent = file_get_contents($this->testImagePath);
        [$fileName, $imageContent, $absolutePath, $result] = ['image.jpg', $imageContent, 'absolutePath', false];
        $this->mediaDirectoryMock->expects($this->once())->method('getAbsolutePath')
            ->with(Wrapping::IMAGE_PATH . $fileName)->willReturn($absolutePath);
        $this->mediaDirectoryMock->expects($this->once())->method('writeFile')
            ->with(Wrapping::IMAGE_TMP_PATH . $absolutePath, $imageContent)->willReturn($result);
        $this->assertFalse($this->wrapping->attachBinaryImage($fileName, $imageContent));
    }

    public function testAttachBinaryImage()
    {
        $imageContent = file_get_contents($this->testImagePath);
        [$fileName, $imageContent, $absolutePath, $result] = ['image.jpg', $imageContent, 'absolutePath', true];
        $this->mediaDirectoryMock->expects($this->once())->method('getAbsolutePath')
            ->with(Wrapping::IMAGE_PATH . $fileName)->willReturn($absolutePath);
        $this->mediaDirectoryMock->expects($this->once())->method('writeFile')
            ->with(Wrapping::IMAGE_TMP_PATH . $absolutePath, $imageContent)->willReturn($result);

        $this->assertEquals($absolutePath, $this->wrapping->attachBinaryImage($fileName, $imageContent));
        $this->assertEquals($fileName, $this->wrapping->getData('tmp_image'));
        $this->assertEquals($fileName, $this->wrapping->getData('image'));
    }

    public function testGetBaseCurrencyCodeWhenItNotExists()
    {
        $storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $store = $this->getMockBuilder(Store::class)
            ->addMethods(['getBaseCurrencyCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $wrapping = $this->objectManagerHelper->getObject(
            Wrapping::class,
            [
                'storeManager' => $storeManager,
            ]
        );
        $storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $store->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->assertEquals('USD', $wrapping->getBaseCurrencyCode());
    }

    public function testGetBaseCurrencyCode()
    {
        $storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $wrapping = $this->objectManagerHelper->getObject(
            Wrapping::class,
            [
                'storeManager' => $storeManager,
                'data' => [
                    'base_currency_code' => 'EU',
                ],
            ]
        );
        $storeManager->expects($this->never())->method('getStore');
        $this->assertEquals('EU', $wrapping->getBaseCurrencyCode());
    }
}
