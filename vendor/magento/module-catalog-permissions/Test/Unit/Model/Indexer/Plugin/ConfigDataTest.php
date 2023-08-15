<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

use Magento\Catalog\Model\Category;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\Plugin\ConfigData;
use Magento\CatalogPermissions\Model\Indexer\Product;
use Magento\Config\Model\Config;
use Magento\Config\Model\Config\Loader;
use Magento\Framework\App\Cache;
use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\App\Cache\Type\Layout;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigDataTest extends TestCase
{
    /**
     * @var CacheInterface|MockObject
     */
    protected $coreCacheMock;

    /**
     * @var IndexerInterface|MockObject
     */
    protected $indexerMock;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $appConfigMock;

    /**
     * @var Loader|MockObject
     */
    protected $configLoaderMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Closure|MockObject
     */
    protected $closureMock;

    /**
     * @var Config|MockObject
     */
    protected $backendConfigMock;

    /**
     * @var ConfigData
     */
    protected $configData;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistryMock;

    protected function setUp(): void
    {
        $this->coreCacheMock = $this->createPartialMock(Cache::class, ['clean']);
        $this->appConfigMock = $this->createPartialMock(
            \Magento\CatalogPermissions\App\Backend\Config::class,
            ['isEnabled']
        );
        $this->indexerMock = $this->createPartialMock(Indexer::class, ['getId', 'invalidate']);
        $this->configLoaderMock = $this->createPartialMock(
            Loader::class,
            ['getConfigByPath']
        );
        $this->storeManagerMock = $this->createPartialMock(
            StoreManager::class,
            ['getStore', 'getWebsite']
        );
        $backendConfigMock = $this->backendConfigMock = $this->getMockBuilder(Config::class)
            ->addMethods(['getStore', 'getWebsite', 'getSection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->closureMock = function () use ($backendConfigMock) {
            return $backendConfigMock;
        };

        $this->indexerRegistryMock = $this->createPartialMock(
            IndexerRegistry::class,
            ['get']
        );

        $this->configData = new ConfigData(
            $this->coreCacheMock,
            $this->appConfigMock,
            $this->indexerRegistryMock,
            $this->configLoaderMock,
            $this->storeManagerMock
        );
    }

    public function testAroundSaveWithoutChanges()
    {
        $section = 'test';
        $this->backendConfigMock->expects($this->exactly(2))->method('getStore')->willReturn(false);
        $this->backendConfigMock->expects($this->exactly(2))->method('getWebsite')->willReturn(false);
        $this->backendConfigMock->expects($this->exactly(2))->method('getSection')->willReturn($section);
        $this->configLoaderMock->expects(
            $this->exactly(2)
        )->method(
            'getConfigByPath'
        )->with(
            $section . '/magento_catalogpermissions',
            'default',
            0,
            false
        )->willReturn(
            ['test' => 1]
        );
        $this->appConfigMock->expects($this->never())->method('isEnabled');

        $this->indexerRegistryMock->expects($this->never())->method('get');

        $this->configData->aroundSave($this->backendConfigMock, $this->closureMock);
    }

    public function testAroundSaveIndexerTurnedOff()
    {
        $section = 'test';
        $storeId = 5;

        $store = $this->getStore();
        $store->expects($this->exactly(2))->method('getId')->willReturn($storeId);
        $this->backendConfigMock->expects($this->exactly(4))->method('getStore')->willReturn($store);
        $this->storeManagerMock->expects($this->exactly(2))->method('getStore')->willReturn($store);

        $this->backendConfigMock->expects($this->never())->method('getWebsite');

        $this->backendConfigMock->expects($this->exactly(2))->method('getSection')->willReturn($section);
        $this->prepareConfigLoader($section, $storeId, 'stores');

        $this->appConfigMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->coreCacheMock->expects($this->never())->method('clean');

        $this->configData->aroundSave($this->backendConfigMock, $this->closureMock);
    }

    public function testAroundSaveIndexerTurnedOn()
    {
        $section = 'test';
        $websiteId = 20;

        $store = $this->getStore();
        $store->expects($this->exactly(2))->method('getId')->willReturn($websiteId);
        $this->backendConfigMock->expects($this->exactly(4))->method('getWebsite')->willReturn($store);
        $this->storeManagerMock->expects($this->exactly(2))->method('getWebsite')->willReturn($store);

        $this->storeManagerMock->expects($this->never())->method('getStore');

        $this->backendConfigMock->expects($this->exactly(2))->method('getStore');

        $this->backendConfigMock->expects($this->exactly(2))->method('getSection')->willReturn($section);

        $this->prepareConfigLoader($section, $websiteId, 'websites');

        $this->appConfigMock->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->coreCacheMock->expects(
            $this->once()
        )->method(
            'clean'
        )->with(
            [
                Category::CACHE_TAG,
                Block::CACHE_TAG,
                Layout::CACHE_TAG
            ]
        );

        $this->indexerMock->expects($this->exactly(2))->method('invalidate');

        $this->indexerRegistryMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->equalTo(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)],
                [$this->equalTo(Product::INDEXER_ID)]
            )
            ->willReturn($this->indexerMock);

        $this->configData->aroundSave($this->backendConfigMock, $this->closureMock);
    }

    /**
     * @return Store|MockObject
     */
    protected function getStore()
    {
        $store = $this->createPartialMock(Store::class, ['getId']);
        return $store;
    }

    /**
     * @return Website|MockObject
     */
    protected function getWebsite()
    {
        $website = $this->createPartialMock(Website::class, ['getId']);
        return $website;
    }

    /**
     * @param string $section
     * @param int $objectId
     * @param string $type
     */
    protected function prepareConfigLoader($section, $objectId, $type)
    {
        $counter = 0;
        $this->configLoaderMock->expects(
            $this->exactly(2)
        )->method(
            'getConfigByPath'
        )->with(
            $section . '/magento_catalogpermissions',
            $type,
            $objectId,
            false
        )->willReturnCallback(
            function () use (&$counter) {
                return (++$counter) % 2 ? ['test' => 1] : ['test' => 2];
            }
        );
    }
}
