<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\ResourceModel\Report\DataCount;

use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\ResourceModel\Report\DataCount\ProductAttributes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductAttributesTest extends TestCase
{
    /**
     * @var ProductAttributes
     */
    protected $productAttributes;

    /**
     * @var \Magento\Eav\Model\ConfigFactory|MockObject
     */
    protected $eavConfigFactoryMock;

    /**
     * @var Config|MockObject
     */
    protected $eavConfigMock;

    /**
     * @var Type|MockObject
     */
    protected $entityTypeMock;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $connectionMock;

    /**
     * @var Category|MockObject
     */
    protected $catalogResourceMock;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);

        $this->eavConfigFactoryMock = $this->createPartialMock(\Magento\Eav\Model\ConfigFactory::class, ['create']);
        $this->eavConfigMock = $this->createMock(Config::class);

        $this->entityTypeMock = $this->createMock(Type::class);
        $this->entityTypeMock = $this->getMockBuilder(Type::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->catalogResourceMock = $this->createPartialMock(
            Category::class,
            ['getConnection', 'getTable']
        );
        $this->catalogResourceMock->expects($this->any())->method('getConnection')->willReturn(
            $this->connectionMock
        );

        $this->productAttributes = $this->objectManagerHelper->getObject(
            ProductAttributes::class,
            [
                'eavConfigFactory' => $this->eavConfigFactoryMock,
                'catalogResource' => $this->catalogResourceMock
            ]
        );
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecute()
    {
        $expectedResult = 19740;
        $entityTypeId = 4;
        $type = 'catalog_product';

        $tableDescription = [
            'entity_id' => [
                'SCHEMA_NAME' => null,
                'TABLE_NAME' => 'catalog_product_entity',
                'COLUMN_NAME' => 'entity_id',
                'COLUMN_POSITION' => 1,
                'DATA_TYPE' => 'int',
                'DEFAULT' => null,
                'NULLABLE' => false,
                'LENGTH' => null,
                'SCALE' => null,
                'PRECISION' => null,
                'UNSIGNED' => true,
                'PRIMARY' => true,
                'PRIMARY_POSITION' => 1,
                'IDENTITY' => true
            ],
            'attribute_set_id' => [
                'SCHEMA_NAME' => null,
                'TABLE_NAME' => 'catalog_product_entity',
                'COLUMN_NAME' => 'attribute_set_id',
                'COLUMN_POSITION' => 2,
                'DATA_TYPE' => 'smallint',
                'DEFAULT' => '0',
                'NULLABLE' => false,
                'LENGTH' => null,
                'SCALE' => null,
                'PRECISION' => null,
                'UNSIGNED' => true,
                'PRIMARY' => false,
                'PRIMARY_POSITION' => null,
                'IDENTITY' => false
            ],
            'type_id' => [
                'SCHEMA_NAME' => null,
                'TABLE_NAME' => 'catalog_product_entity',
                'COLUMN_NAME' => 'type_id',
                'COLUMN_POSITION' => 3,
                'DATA_TYPE' => 'varchar',
                'DEFAULT' => 'simple',
                'NULLABLE' => false,
                'LENGTH' => '32',
                'SCALE' => null,
                'PRECISION' => null,
                'UNSIGNED' => null,
                'PRIMARY' => false,
                'PRIMARY_POSITION' => null,
                'IDENTITY' => false
            ],
            'sku' => [
                'SCHEMA_NAME' => null,
                'TABLE_NAME' => 'catalog_product_entity',
                'COLUMN_NAME' => 'sku',
                'COLUMN_POSITION' => 4,
                'DATA_TYPE' => 'varchar',
                'DEFAULT' => null,
                'NULLABLE' => true,
                'LENGTH' => '64',
                'SCALE' => null,
                'PRECISION' => null,
                'UNSIGNED' => null,
                'PRIMARY' => false,
                'PRIMARY_POSITION' => null,
                'IDENTITY' => false
            ],
            'has_options' => [
                'SCHEMA_NAME' => null,
                'TABLE_NAME' => 'catalog_product_entity',
                'COLUMN_NAME' => 'has_options',
                'COLUMN_POSITION' => 5,
                'DATA_TYPE' => 'smallint',
                'DEFAULT' => '0',
                'NULLABLE' => false,
                'LENGTH' => null,
                'SCALE' => null,
                'PRECISION' => null,
                'UNSIGNED' => null,
                'PRIMARY' => false,
                'PRIMARY_POSITION' => null,
                'IDENTITY' => false
            ],
            'required_options' => [
                'SCHEMA_NAME' => null,
                'TABLE_NAME' => 'catalog_product_entity',
                'COLUMN_NAME' => 'required_options',
                'COLUMN_POSITION' => 6,
                'DATA_TYPE' => 'smallint',
                'DEFAULT' => '0',
                'NULLABLE' => false,
                'LENGTH' => null,
                'SCALE' => null,
                'PRECISION' => null,
                'UNSIGNED' => true,
                'PRIMARY' => false,
                'PRIMARY_POSITION' => null,
                'IDENTITY' => false
            ],
            'created_at' => [
                'SCHEMA_NAME' => null,
                'TABLE_NAME' => 'catalog_product_entity',
                'COLUMN_NAME' => 'created_at',
                'COLUMN_POSITION' => 7,
                'DATA_TYPE' => 'timestamp',
                'DEFAULT' => null,
                'NULLABLE' => true,
                'LENGTH' => null,
                'SCALE' => null,
                'PRECISION' => null,
                'UNSIGNED' => null,
                'PRIMARY' => false,
                'PRIMARY_POSITION' => null,
                'IDENTITY' => false
            ],
            'updated_at' => [
                'SCHEMA_NAME' => null,
                'TABLE_NAME' => 'catalog_product_entity',
                'COLUMN_NAME' => 'updated_at',
                'COLUMN_POSITION' => 8,
                'DATA_TYPE' => 'timestamp',
                'DEFAULT' => null,
                'NULLABLE' => true,
                'LENGTH' => null,
                'SCALE' => null,
                'PRECISION' => null,
                'UNSIGNED' => null,
                'PRIMARY' => false,
                'PRIMARY_POSITION' => null,
                'IDENTITY' => false
            ]
        ];

        $info = [
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'static'],
            ['backend_type' => 'int'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'static'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'datetime'],
            ['backend_type' => 'datetime'],
            ['backend_type' => 'text'],
            ['backend_type' => 'text'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'int'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'static'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'int'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'int'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'text'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'datetime'],
            ['backend_type' => 'datetime'],
            ['backend_type' => 'int'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'static'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'int'],
            ['backend_type' => 'text'],
            ['backend_type' => 'static'],
            ['backend_type' => 'int'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'datetime'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'datetime'],
            ['backend_type' => 'int'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'int'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'static'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'varchar'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'int'],
            ['backend_type' => 'decimal'],
            ['backend_type' => 'int'],
        ];

        $this->eavConfigFactoryMock->expects($this->once())->method('create')->willReturn($this->eavConfigMock);
        $this->eavConfigMock->expects($this->once())->method('getEntityType')->with($type)->willReturn(
            $this->entityTypeMock
        );
        $this->entityTypeMock->expects($this->once())->method('getId')->willReturn($entityTypeId);

        $this->catalogResourceMock->expects($this->atLeastOnce())->method('getTable')->willReturnMap(
            [
                ['catalog_eav_attribute', 'catalog_eav_attribute'],
                ['eav_attribute', 'eav_attribute'],
                ['catalog_product_entity', 'catalog_product_entity']
            ]
        );
        $this->connectionMock->expects($this->once())->method('fetchAll')->willReturn($info);
        $this->connectionMock->expects($this->once())->method('describeTable')->willReturn($tableDescription);

        $this->assertSame($expectedResult, $this->productAttributes->getProductAttributesRowSizeForFlatTable());
    }
}
