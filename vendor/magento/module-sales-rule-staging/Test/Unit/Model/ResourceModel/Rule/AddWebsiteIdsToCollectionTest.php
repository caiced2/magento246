<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model\ResourceModel\Rule;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb as ResourceModel;
use Magento\SalesRuleStaging\Model\ResourceModel\Rule\AddWebsiteIdsToCollection;
use PHPUnit\Framework\TestCase;

/**
 * Test add website ids to sales rules collection
 */
class AddWebsiteIdsToCollectionTest extends TestCase
{
    /**
     * @var AddWebsiteIdsToCollection
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new AddWebsiteIdsToCollection(
            new DataObject(
                [
                    'website' => [
                        'associations_table' => 'salesrule_website',
                        'rule_id_field' => 'row_id',
                        'entity_id_field' => 'website_id',
                    ]
                ]
            )
        );
    }

    /**
     * Test that exception is thrown if wrong configuration is passed to the constructor
     */
    public function testShouldThrowException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->model = new AddWebsiteIdsToCollection(
            new DataObject(
                [
                    'website' => [
                        'associations_table' => '',
                        'rule_id_field' => '',
                    ]
                ]
            )
        );
    }

    /**
     * Test that website ids is set for each item in the collection
     */
    public function testExecute(): void
    {
        $data = [
            [
                'row_id' => 1,
                'website_id' => 1
            ],
            [
                'row_id' => 1,
                'website_id' => 2
            ],
            [
                'row_id' => 2,
                'website_id' => 2
            ]
        ];
        $items = [
            new DataObject(['row_id' => 1]),
            new DataObject(['row_id' => 2]),
            new DataObject(['row_id' => 3]),
        ];
        $select = $this->createMock(Select::class);
        $select->method('from')
            ->willReturnSelf();
        $select->method('where')
            ->willReturnSelf();
        $connection = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchAll', 'select', '_connect'])
            ->getMockForAbstractClass();
        $connection->method('fetchAll')
            ->willReturn($data);
        $connection->method('select')
            ->willReturn($select);
        $resource = $this->getMockBuilder(ResourceModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTable'])
            ->getMockForAbstractClass();
        $resource->method('getTable')
            ->willReturnArgument(0);
        $collection = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems', 'getConnection', 'getResource', 'getIterator', 'load'])
            ->getMockForAbstractClass();
        $collection->method('getItems')
            ->willReturn($items);
        $collection->method('getConnection')
            ->willReturn($connection);
        $collection->method('getResource')
            ->willReturn($resource);
        $collection->method('getIterator')
            ->willReturn(new \ArrayIterator($items));

        $this->model->execute($collection);
        $this->assertEquals([1, 2], $items[0]->getWebsiteIds());
        $this->assertEquals([2], $items[1]->getWebsiteIds());
        $this->assertNull($items[2]->getWebsiteIds());
    }
}
