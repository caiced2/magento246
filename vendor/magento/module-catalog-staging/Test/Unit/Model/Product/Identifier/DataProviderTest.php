<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Product\Identifier;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogStaging\Model\Product\Identifier\DataProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\Store;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var DataProvider
     */
    private $model;

    /**
     * @var MockObject
     */
    private $collectionMock;

    /**
     * @var MockObject
     */
    private $poolMock;

    /**
     * @var MockObject
     */
    private $requestMock;

    protected function setUp(): void
    {
        $this->collectionMock = $this->createMock(Collection::class);
        $collectionFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $collectionFactoryMock->expects($this->once())->method('create')->willReturn($this->collectionMock);
        $this->poolMock = $this->getMockForAbstractClass(PoolInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->model = new DataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $collectionFactoryMock,
            $this->poolMock,
            $this->requestMock
        );
    }

    public function testGetData()
    {
        $productId = 100;
        $productName = 'name';
        $storeId = 1;
        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->exactly(2))->method('getId')->willReturn($productId);
        $productMock->expects($this->once())->method('getName')->willReturn($productName);

        $this->collectionMock->expects($this->once())->method('getItems')->willReturn([$productMock]);
        $this->requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with('store', Store::DEFAULT_STORE_ID)
            ->willReturn($storeId);

        $expectedResult = [
            $productId => [
                'entity_id' => $productId,
                'name' => $productName,
                'store_id' => $storeId
            ]
        ];

        $this->assertEquals($expectedResult, $this->model->getData());
    }
}
