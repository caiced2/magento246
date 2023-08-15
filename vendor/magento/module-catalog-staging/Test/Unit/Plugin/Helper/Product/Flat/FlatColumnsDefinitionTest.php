<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Plugin\Helper\Product\Flat;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Product\Flat\Indexer;
use Magento\CatalogStaging\Plugin\Helper\Product\Flat\FlatColumnsDefinition;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlatColumnsDefinitionTest extends TestCase
{
    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var EntityMetadataInterface|MockObject
     */
    private $metadataMock;

    /**
     * @var Indexer|MockObject
     */
    private $indexerMock;

    /**
     * @var FlatColumnsDefinition
     */
    private $plugin;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->indexerMock = $this->getMockBuilder(Indexer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = $objectManagerHelper->getObject(
            FlatColumnsDefinition::class,
            [
                'metadataPool' => $this->metadataPoolMock
            ]
        );
    }

    public function testAfterGetFlatColumnsDdlDefinition()
    {
        $linkField = 'row_id';
        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($this->metadataMock);
        $this->metadataMock->expects($this->once())->method('getLinkField')
            ->willReturn($linkField);
        $expected = [
            $linkField => [
                'type' => Table::TYPE_INTEGER,
                'length' => null,
                'unsigned' => true,
                'nullable' => false,
                'default' => false,
                'comment' => 'Row Id',
            ]
        ];
        $this->assertEquals($expected, $this->plugin->afterGetFlatColumnsDdlDefinition($this->indexerMock, []));
    }
}
