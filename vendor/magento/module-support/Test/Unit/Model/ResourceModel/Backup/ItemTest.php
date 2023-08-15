<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\ResourceModel\Backup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Backup\AbstractItem;
use Magento\Support\Model\ResourceModel\Backup\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Item
     */
    protected $item;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resource;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $connection;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resource = $this->createMock(ResourceConnection::class);
        $this->resource->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        /** @var Context $context */
        $context = $this->objectManagerHelper->getObject(
            Context::class,
            ['resource' => $this->resource]
        );

        $this->item = $this->objectManagerHelper->getObject(
            Item::class,
            ['context' => $context]
        );
    }

    /**
     * @return void
     */
    public function testLoadItemByBackupIdAndType()
    {
        $backupId = 1;
        $type = 2;

        /** @var Select|MockObject $select */
        $select = $this->createMock(Select::class);
        $select->expects($this->once())
            ->method('from')
            ->willReturnSelf();
        $select->expects($this->any())
            ->method('where')
            ->willReturnMap([
                ['backup_id = ?', $backupId, null, $select],
                ['type = ?', $type, null, $select],
            ]);

        $collectionData = ['someKey' => 'someValue'];
        $this->connection->expects($this->once())
            ->method('select')
            ->willReturn($select);
        $this->connection->expects($this->once())
            ->method('fetchRow')
            ->with($select)
            ->willReturn($collectionData);

        /** @var AbstractItem|MockObject $abstractItem */
        $abstractItem = $this->getMockBuilder(AbstractItem::class)
            ->setMethods(['addData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $abstractItem->expects($this->once())
            ->method('addData')
            ->with($collectionData)
            ->willReturnSelf();

        $this->assertEquals($this->item, $this->item->loadItemByBackupIdAndType($abstractItem, $backupId, $type));
    }
}
