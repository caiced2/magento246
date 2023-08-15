<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Product\Locator;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StagingLocatorTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $versionManagerMock;

    /**
     * @var MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var string
     */
    private $requestFieldName;

    /**
     * @var MockObject
     */
    private $productRepositoryMock;

    /**
     * @var MockObject
     */
    private $storeManagerMock;

    /**
     * @var MockObject
     */
    private $registryMock;

    /**
     * @var StagingLocator
     */
    private $locator;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);
        $this->productRepositoryMock = $this->getMockForAbstractClass(ProductRepositoryInterface::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->requestFieldName = 'fieldName';

        $this->locator = new StagingLocator(
            $this->registryMock,
            $this->requestMock,
            $this->versionManagerMock,
            $this->updateRepositoryMock,
            $this->productRepositoryMock,
            $this->storeManagerMock,
            $this->requestFieldName
        );
    }

    public function testGetProductRetrievesProductFromRegistryIfPresent()
    {
        $entityId = 1;
        $productMock = $this->getMockForAbstractClass(ProductInterface::class);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['update_id', null, null],
                [$this->requestFieldName, null, $entityId],
            ]);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn($productMock);
        $this->assertEquals($productMock, $this->locator->getProduct());
    }

    public function testGetProductRetrievesProductFromRepositoryIfProductIsNotInRegistry()
    {
        $entityId = 1;
        $storeId = 1;
        $storeMock = $this->getMockForAbstractClass(StoreInterface::class);
        $storeMock->expects($this->any())->method('getId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->with($storeId)->willReturn($storeMock);

        $productMock = $this->getMockForAbstractClass(ProductInterface::class);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['update_id', null, null],
                [$this->requestFieldName, null, $entityId],
                ['store', 0, $storeId],
            ]);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($entityId, true, $storeId, false)
            ->willReturn($productMock);

        $this->assertEquals($productMock, $this->locator->getProduct());
    }

    public function testGetStoreRetrievesStoreFromRegistryIfPresent()
    {
        $storeMock = $this->getMockForAbstractClass(StoreInterface::class);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_store')
            ->willReturn($storeMock);

        $this->assertEquals($storeMock, $this->locator->getStore());
    }

    public function testGetStoreRetrievesStoreFromStoreManagerIfStoreIsNotInRegistry()
    {
        $storeId = 1;
        $storeMock = $this->getMockForAbstractClass(StoreInterface::class);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('store', 0)
            ->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->with($storeId)->willReturn($storeMock);

        $this->assertEquals($storeMock, $this->locator->getStore());
    }
}
