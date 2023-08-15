<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Enterprise\Test\Unit\Model\Favicon;

use Magento\Enterprise\Model\Plugin\Favicon as Favicon;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Favicon\Favicon as ThemeFavicon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FaviconTest extends TestCase
{
    /**
     * @var ThemeFavicon
     */
    protected $themeOject;

    /**
     * @var Favicon
     */
    protected $object;

    /**
     * @var MockObject|Store
     */
    protected $store;

    /**
     * @var MockObject|ScopeConfigInterface
     */
    protected $scopeManager;

    /**
     * @var MockObject|Database
     */
    protected $fileStorageDatabase;

    /**
     * @var MockObject|ReadInterface
     */
    protected $mediaDir;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();
        $this->store = $this->getMockBuilder(
            Store::class
        )->disableOriginalConstructor()
            ->getMock();
        $storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);
        /** @var StoreManagerInterface $storeManager */
        $this->scopeManager = $this->getMockBuilder(
            ScopeConfigInterface::class
        )->getMock();
        $this->fileStorageDatabase = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mediaDir = $this->getMockBuilder(
            ReadInterface::class
        )->getMock();
        $filesystem->expects($this->once())
            ->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->mediaDir);
        /** @var Filesystem $filesystem */
        $this->themeOject = new ThemeFavicon(
            $storeManager,
            $this->scopeManager,
            $this->fileStorageDatabase,
            $filesystem
        );
        $this->object = new Favicon();
    }

    /**
     * cover getDefaultFavicon.
     *
     * @return void
     */
    public function testGetDefaultFavicon(): void
    {
        $this->assertEquals(
            'Magento_Enterprise::favicon.ico',
            $this->object->afterGetDefaultFavicon($this->themeOject)
        );
    }
}
