<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Test\Unit\Model\ResourceModel\Report\Invitation\Order;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Invitation\Model\ResourceModel\Invitation;
use Magento\Invitation\Model\ResourceModel\Report\Invitation\Order\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CollectionTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $resourceMock;

    /**
     * @var MockObject
     */
    protected $connectionMock;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var MockObject
     */
    protected $select;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $entityFactory = $this->getMockForAbstractClass(
            EntityFactoryInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $logger = $this->getMockForAbstractClass(
            LoggerInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $fetchStrategy = $this->getMockForAbstractClass(
            FetchStrategyInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->connectionMock = $this->getMockForAbstractClass(
            Mysql::class,
            [],
            '',
            false,
            false,
            true,
            ['select', 'fetchPairs', 'prepareSqlCondition', 'fetchAssoc']
        );
        $this->select = $this->getMockForAbstractClass(
            Select::class,
            [],
            '',
            false,
            false,
            true,
            ['from', 'columns', 'where', 'reset', 'group']
        );
        /**
         * @var MockObject $contextMock
         */
        $contextMock = $this->createPartialMock(
            Context::class,
            ['getResources']
        );
        $resource = $this->getMockForAbstractClass(
            AbstractDb::class,
            [],
            '',
            false,
            false,
            true,
            ['getConnection', 'getMainTable', 'getTable']
        );

        $resource->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);
        $contextMock->expects($this->once())->method('getResources')->willReturn($resource);
        $this->resourceMock = $this->getMockForAbstractClass(
            Invitation::class,
            ['context' => $contextMock],
            '',
            true,
            false,
            true,
            ['getConnection', 'getMainTable', 'getTable']
        );

        $this->resourceMock->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())->method('getMainTable')->willReturn('main_table');
        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->select);

        $objectManager = new ObjectManager($this);
        $this->collection = $objectManager->getObject(
            Collection::class,
            [
                'entityFactory' => $entityFactory,
                'logger' => $logger,
                'fetchStrategy' => $fetchStrategy,
                'connection' => $this->connectionMock,
                'resource' => $this->resourceMock
            ]
        );
    }

    /**
     * Checks getPurchasedNumber in Invitation conversion report.
     *
     * @param array $customerToStoreMap
     * @param array $orderCountsByStoreMap
     * @param int $expectedCnt
     *
     * @return void
     * @dataProvider getPurchasedNumberDataProvider
     */
    public function testGetPurchaseNumber(
        array $customerToStoreMap,
        array $orderCountsByStoreMap,
        int $expectedCnt
    ): void {
        $tableOrderName = 'sales_order';
        $expectedCondition = 'o.customer_id IN (' . implode(',', array_keys($customerToStoreMap)) . ')';
        $item = new DataObject(['id' => 1]);
        $this->collection->addItem($item);
        $this->connectionMock->expects($this->once())->method('fetchPairs')->willReturn($customerToStoreMap);
        $this->resourceMock->expects($this->once())
            ->method('getTable')
            ->with($tableOrderName)
            ->willReturn($tableOrderName);
        $this->select->expects($this->any())->method('reset')->willReturnSelf();
        $this->select->expects($this->once())->method('columns')->willReturnSelf();
        $this->select->expects($this->once())->method('from')->with(
            ['o' => $tableOrderName],
            ['o.store_id', 'COUNT(DISTINCT o.customer_id) as cnt']
        )->willReturnSelf();
        $this->select
            ->method('where')
            ->withConsecutive([], [$expectedCondition])
            ->willReturnOnConsecutiveCalls($this->select, $this->select);
        $this->connectionMock->expects($this->once())
            ->method('prepareSqlCondition')
            ->with('o.customer_id', ['in' => array_keys($customerToStoreMap)])
            ->willReturn($expectedCondition);
        $this->select->expects($this->once())->method('group')->with(['o.store_id'])->willReturnSelf();
        $this->connectionMock->expects($this->once())->method('fetchAssoc')->willReturn($orderCountsByStoreMap);
        $this->collection->load();
        $this->assertEquals($expectedCnt, $item->getPurchased());
    }

    /**
     * @return array
     */
    public function getPurchasedNumberDataProvider(): array
    {
        return [
            [
                'customerToStoreMap' => [
                    1 => 3,
                    2 => 4
                ],
                'orderCountsByStoreMap' => [
                    3 => [
                        'cnt' => 35
                    ],
                    4 => [
                        'cnt' => 25
                    ]
                ],
                'expectedCnt' => 60
            ],
            [
                'customerToStoreMap' => [
                    1 => 3,
                    2 => 3
                ],
                'orderCountsByStoreMap' => [
                    3 => [
                        'cnt' => 20
                    ],
                    4 => [
                        'cnt' => 25
                    ]
                ],
                'expectedCnt' => 20
            ]
        ];
    }
}
