<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Indexer\Category\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\CatalogStaging\Model\Indexer\Category\Product\Preview;
use Magento\CatalogStaging\Model\Indexer\Category\Product\PreviewReindex;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PreviewReindexTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var MockObject
     */
    private $previewMock;

    /**
     * @var MockObject
     */
    private $categoryRepositoryMock;

    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $tableResolver;

    /**
     * @var PreviewReindex
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->resourceConnectionMock->method('getTableName')
            ->willReturnArgument(0);
        $this->previewMock = $this->getMockBuilder(
            Preview::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);

        $connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $resource = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods([
                'getConnection',
                'getTableName'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $resource->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $resource->expects($this->any())->method('getTableName')->willReturnArgument(0);

        $this->tableResolver = $objectManager->getObject(
            IndexScopeResolver::class,
            [
                'resource' => $resource
            ]
        );

        $this->model = $objectManager->getObject(
            PreviewReindex::class,
            [
                'resourceConnection' => $this->resourceConnectionMock,
                'preview' => $this->previewMock,
                'categoryRepository' => $this->categoryRepositoryMock,
                'tableResolver' => $this->tableResolver
            ]
        );
    }

    public function testReindexAlreadyMapped()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Table catalog_category_product_index_store0 already mapped');
        $categoryId = 1;
        $storeId = 0;
        $indexTableTmp = 'index_tmp';

        $categoryMock = $this->createMock(Category::class);
        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willReturn($categoryMock);

        $this->previewMock->expects($this->once())
            ->method('executeScoped')
            ->with($categoryId, $storeId);
        $this->previewMock->expects($this->once())
            ->method('getTemporaryTable')
            ->willReturn($indexTableTmp);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getMappedTableName')
            ->with('catalog_category_product_index_store0')
            ->willReturn($indexTableTmp);

        $this->model->reindex($categoryId, $storeId);
    }

    public function testReindex()
    {
        $categoryId = 1;
        $storeId = 0;
        $indexTableTmp = 'index_tmp';

        $categoryMock = $this->createMock(Category::class);
        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willReturn($categoryMock);

        $this->previewMock->expects($this->once())
            ->method('executeScoped')
            ->with($categoryId, $storeId);
        $this->previewMock->expects($this->once())
            ->method('getTemporaryTable')
            ->willReturn($indexTableTmp);

        $this->resourceConnectionMock->expects($this->once())
            ->method('getMappedTableName')
            ->with('catalog_category_product_index_store0')
            ->willReturn(false);

        $this->resourceConnectionMock->expects($this->once())
            ->method('setMappedTableName')
            ->with('catalog_category_product_index_store0', $indexTableTmp);

        $this->model->reindex($categoryId, $storeId);
    }
}
