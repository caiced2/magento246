<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Plugin\Controller;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\CatalogStaging\Model\Indexer\Category\Product\Preview;
use Magento\CatalogStaging\Model\Indexer\Category\Product\PreviewReindex;
use Magento\CatalogStaging\Model\Plugin\Controller\View;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $versionManagerMock;

    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $storeManager;

    /**
     * @var View
     */
    private $model;

    /**
     * @var MockObject
     */
    private $previewReindex;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->previewReindex = $this->getMockBuilder(PreviewReindex::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store = $this->getMockBuilder(StoreInterface::class)
            ->setMethods([
                'getId',
            ])
            ->getMockForAbstractClass();
        $store->expects($this->any())
            ->method('getId')
            ->willReturn(0);
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods([
                'getStore',
            ])
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($store);
        $objectManager = new ObjectManager($this);

        $this->model = $objectManager->getObject(
            View::class,
            [
                'versionManager' => $this->versionManagerMock,
                'storeManager' => $this->storeManager,
                'previewReindex' => $this->previewReindex
            ]
        );
    }

    public function testBeforeExecuteNotPreview()
    {
        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(false);
        $viewMock->expects($this->never())
            ->method('getRequest');

        $this->model->beforeExecute($viewMock);
    }

    public function testBeforeExecuteNoCategory()
    {
        $categoryId = null;

        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willReturn($categoryId);
        $this->previewReindex->expects($this->once())
            ->method('reindex');

        $this->model->beforeExecute($viewMock);
    }

    public function testBeforeExecuteValidCategory()
    {
        $categoryId = 1;
        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willReturn($categoryId);

        $this->model->beforeExecute($viewMock);
    }
}
