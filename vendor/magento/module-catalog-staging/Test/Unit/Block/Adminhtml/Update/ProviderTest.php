<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Block\Adminhtml\Update;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogStaging\Block\Adminhtml\Update\Provider;
use Magento\CatalogStaging\Ui\Component\Listing\Column\Product\UrlProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase
{
    /**
     * @var Provider
     */
    private $model;

    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $productRepositoryMock;

    /**
     * @var MockObject
     */
    private $versionManagerMock;

    /**
     * @var MockObject
     */
    private $urlProviderMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->productRepositoryMock = $this->getMockForAbstractClass(ProductRepositoryInterface::class);
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->urlProviderMock = $this->createMock(
            UrlProvider::class
        );

        $this->model = new Provider(
            $this->requestMock,
            $this->productRepositoryMock,
            $this->versionManagerMock,
            $this->urlProviderMock
        );
    }

    public function testGetId()
    {
        $productId = 100;

        $productMock = $this->getMockForAbstractClass(ProductInterface::class);
        $productMock->expects($this->once())->method('getId')->willReturn($productId);

        $this->requestMock->expects($this->once())->method('getParam')->with('id')->willReturn($productId);
        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willReturn($productMock);

        $this->assertEquals($productId, $this->model->getId());
    }

    public function testGetIdThrowsExceptionIfProductDoesNotExist()
    {
        $productId = 100;

        $this->requestMock->expects($this->once())->method('getParam')->with('id')->willReturn($productId);
        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willThrowException(new NoSuchEntityException());
        $this->assertNull($this->model->getId());
    }

    public function testGetUrlReturnsUrlBasedOnProductDataIfProductExists()
    {
        $expectedResult = 'http://www.example.com';
        $currentVersionId = 1;
        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $updateMock->expects($this->any())->method('getId')->willReturn($currentVersionId);
        $this->versionManagerMock->expects($this->any())->method('getCurrentVersion')->willReturn($updateMock);

        $productId = 1;
        $productData = [
            'id' => $productId,
        ];
        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->any())->method('getId')->willReturn($productId);
        $productMock->expects($this->any())->method('getData')->willReturn($productData);

        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn($productId);
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->with($productId)
            ->willReturn($productMock);

        $this->urlProviderMock->expects($this->any())
            ->method('getUrl')
            ->with($productData)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->model->getUrl(1));
    }

    public function testGetUrlReturnsNullIfProductDoesNotExist()
    {
        $currentVersionId = 1;
        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $updateMock->expects($this->any())->method('getId')->willReturn($currentVersionId);
        $this->versionManagerMock->expects($this->any())->method('getCurrentVersion')->willReturn($updateMock);

        $productId = 9999;
        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn($productId);
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->with($productId)
            ->willThrowException(NoSuchEntityException::singleField('id', $productId));

        $this->urlProviderMock->expects($this->never())->method('getUrl');

        $this->assertNull($this->model->getUrl(1));
    }
}
