<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Helper;

use Magento\CatalogPermissions\Helper\Index;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit-test for \Magento\CatalogPermissions\Helper\Index
 */
class IndexTest extends TestCase
{
    /**
     * @var Index
     */
    protected $helper;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceMock;

    /**
     * @var Mysql|MockObject
     */
    protected $connectionMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->connectionMock = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock
            ->expects($this->any())
            ->method('getTableName')
            ->willReturnArgument(0);
        $this->resourceMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn(
                $this->connectionMock
            );

        $this->helper = new Index(
            $this->resourceMock
        );
    }

    /**
     * @return void
     */
    public function testGetChildCategories()
    {
        $selectPathMock = $this->getMockForSelectPath();

        $selectCategoryMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectCategoryMock
            ->expects($this->once())
            ->method('from')
            ->with('catalog_category_entity', ['entity_id'])->willReturnSelf();
        $selectCategoryMock
            ->expects($this->once())
            ->method('order')
            ->with('level ASC')->willReturnSelf();
        $selectCategoryMock
            ->expects($this->once())
            ->method('orWhere')
            ->with('path LIKE ?', '1/2/%')->willReturnSelf();

        $this->connectionMock
            ->expects($this->any())
            ->method('fetchCol')
            ->willReturnMap(
                [
                    [$selectPathMock, [], ['1/2']],
                    [$selectCategoryMock, [], [3, 4]]
                ]
            );

        $this->connectionMock
            ->expects($this->atLeastOnce())
            ->method('select')
            ->will($this->onConsecutiveCalls(
                $selectPathMock,
                $selectCategoryMock
            ));

        $this->assertEquals([3, 4], $this->helper->getChildCategories([987]));
    }

    /**
     * @return void
     */
    public function testGetCategoryList()
    {
        $selectPathMock = $this->getMockForSelectPath();

        $selectCategoryMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectCategoryMock
            ->expects($this->once())
            ->method('from')
            ->with('catalog_category_entity', ['entity_id', 'path'])->willReturnSelf();
        $selectCategoryMock
            ->expects($this->once())
            ->method('order')
            ->with('level ASC')->willReturnSelf();
        $selectCategoryMock
            ->expects($this->once())
            ->method('where')
            ->with('path LIKE ?', '1/2/%')->willReturnSelf();
        $selectCategoryMock
            ->expects($this->once())
            ->method('orWhere')
            ->with('entity_id IN (?)', [1, 2])->willReturnSelf();

        $this->connectionMock
            ->expects($this->once())
            ->method('fetchCol')
            ->with($selectPathMock)
            ->willReturn(['1/2']);
        $this->connectionMock
            ->expects($this->once())
            ->method('fetchPairs')
            ->with($selectCategoryMock)
            ->willReturn([123123]);

        $this->connectionMock
            ->expects($this->atLeastOnce())
            ->method('select')
            ->will($this->onConsecutiveCalls(
                $selectPathMock,
                $selectCategoryMock
            ));

        $this->assertEquals([123123], $this->helper->getCategoryList([987]));
    }

    /**
     * @return void
     */
    public function testGetProductList()
    {
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock
            ->expects($this->once())
            ->method('from')
            ->with('catalog_category_product', 'product_id')->willReturnSelf();
        $selectMock
            ->expects($this->once())
            ->method('distinct')
            ->with(true)->willReturnSelf();
        $selectMock
            ->expects($this->once())
            ->method('where')
            ->with('category_id IN (?)', [1, 2, 3])->willReturnSelf();

        $this->connectionMock
            ->expects($this->any())
            ->method('getTransactionLevel')
            ->willReturn(1);
        $this->connectionMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);
        $this->connectionMock
            ->expects($this->once())
            ->method('fetchCol')
            ->with($selectMock)
            ->willReturn('some result');

        $this->assertEquals('some result', $this->helper->getProductList([1, 2, 3]));
    }

    /**
     * @return MockObject
     */
    private function getMockForSelectPath()
    {
        $selectPathMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectPathMock
            ->expects($this->once())
            ->method('from')
            ->with('catalog_category_entity', ['path'])->willReturnSelf();
        $selectPathMock
            ->expects($this->once())
            ->method('where')
            ->with('entity_id IN (?)', [987])->willReturnSelf();

        return $selectPathMock;
    }
}
