<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Model\ResourceModel\Hierarchy\Node;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var Select|MockObject
     */
    private $selectMock;

    /**
     * @var AbstractDb|MockObject
     */
    private $resourceMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMetadata'])
            ->getMock();

        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $metadataMock->expects($this->any())
            ->method('getLinkField')
            ->willReturn('row_id');

        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with(PageInterface::class)
            ->willReturn($metadataMock);

        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->resourceMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->collection = $objectManagerHelper->getObject(
            Collection::class,
            [
                'resource' => $this->resourceMock,
                'metadataPool' => $this->metadataPoolMock
            ]
        );
    }

    public function testAddStoreFilter()
    {
        $this->collection->setFlag('cms_page_in_stores_data_joined', true);
        $this->connectionMock->method('quoteInto')
            ->with(
                'cmsps.row_id = page_table.row_id AND cmsps.store_id IN (?)',
                [0, 1]
            )
            ->willReturn('cmsps.id = page_table.row_id AND cmsps.store_id IN (0, 1)');
        $this->selectMock->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['cmsps' => $this->collection->getTable('cms_page_store')],
                'cmsps.id = page_table.row_id AND cmsps.store_id IN (0, 1)'
            )
            ->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('where')->with(
                'main_table.page_id IS NULL OR cmsps.store_id IS NOT NULL'
            )
            ->willReturnSelf();
        $this->collection->addStoreFilter(1, true);
    }
}
