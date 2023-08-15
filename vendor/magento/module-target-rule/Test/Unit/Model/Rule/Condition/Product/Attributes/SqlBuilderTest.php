<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Rule\Condition\Product\Attributes;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\Store;
use Magento\TargetRule\Model\ResourceModel\Index;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes\SqlBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for SqlBuilder
 */
class SqlBuilderTest extends TestCase
{
    /**
     * @var SqlBuilder|MockObject
     */
    private $sqlBuilder;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var EntityMetadataInterface|MockObject
     */
    private $entityMetadataMock;

    /**
     * @var Index|MockObject
     */
    private $indexResourceMock;

    /**
     * @var Select|MockObject
     */
    private $selectMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var Attributes|MockObject
     */
    private $attributesMock;

    /**
     * @var Attribute|MockObject
     */
    private $eavAttributeMock;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->indexResourceMock = $this->getMockBuilder(Index::class)
            ->addMethods(['getResource', 'getStoreId'])
            ->onlyMethods(
                [
                    'getTable',
                    'bindArrayOfIds',
                    'getOperatorCondition',
                    'getOperatorBindCondition',
                    'select',
                    'getConnection'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)->disableOriginalConstructor()
            ->onlyMethods(['getIfNullSql', 'getCheckSql'])
            ->getMockForAbstractClass();

        $this->selectMock = $this->createPartialMock(
            Select::class,
            ['from', 'assemble', 'where', 'joinLeft', 'joinInner', 'union']
        );
        $this->metadataPoolMock = $this->createPartialMock(
            MetadataPool::class,
            ['getMetadata']
        );
        $this->entityMetadataMock = $this->getMockBuilder(EntityMetadataInterface::class)->disableOriginalConstructor()
            ->onlyMethods(['getLinkField'])
            ->getMockForAbstractClass();
        $this->eavAttributeMock = $this->createPartialMock(
            Attribute::class,
            ['isScopeGlobal', 'isStatic', 'getBackendTable', 'getId']
        );
        $this->attributesMock = $this->createPartialMock(
            Attributes::class,
            ['getAttributeObject']
        );

        $this->sqlBuilder = new SqlBuilder($this->metadataPoolMock, $this->indexResourceMock);
    }

    /**
     * Tests generating WHERE clause for global scope.
     *
     * @return void
     */
    public function testGenerateWhereClauseForGlobalScopeAttribute(): void
    {
        $attributeId = 42;
        $attributesValue = 3;
        $attributesOperator = '{}';
        $attributeTable = 'catalog_product_entity_varchar';
        $relationTable = 'catalog_product_relation';
        $this->attributesMock->setOperator($attributesOperator);
        $this->attributesMock->setAttribute('filter');
        $this->attributesMock->setValue($attributesValue);
        $bind = [];
        $linkField = 'row_id';
        $this->indexResourceMock->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->indexResourceMock->method('getTable')
            ->withConsecutive(['catalog_product_entity'], [$relationTable])
            ->willReturnOnConsecutiveCalls('catalog_product_entity', $relationTable);
        $this->indexResourceMock->method('getOperatorCondition')
            ->with('table.value', $attributesOperator, $attributesValue)
            ->willReturn('table.value=' . $attributesValue);
        $mainSelect = $this->createPartialMock(
            Select::class,
            ['from', 'assemble', 'where', 'joinLeft', 'joinInner', 'union']
        );
        $childrenSelect = $this->createPartialMock(
            Select::class,
            ['from', 'assemble', 'where', 'joinLeft', 'joinInner', 'union']
        );
        $unionSelect = $this->createPartialMock(
            Select::class,
            ['from', 'assemble', 'where', 'joinLeft', 'joinInner', 'union']
        );
        $this->connectionMock->expects($this->atLeast(3))
            ->method('select')
            ->willReturnOnConsecutiveCalls(
                $mainSelect,
                $childrenSelect,
                $unionSelect
            );
        $mainSelect->method('from')
            ->willReturnSelf();
        $mainSelect->expects($this->once())
            ->method('joinLeft')
            ->with(['cpe' => 'catalog_product_entity'], "cpe.$linkField = table.$linkField", [])
            ->willReturnSelf();
        $mainSelect->expects($this->once())
            ->method('joinInner')
            ->with(['relation' => 'catalog_product_relation'], "cpe.entity_id = relation.child_id", [])
            ->willReturnSelf();
        $mainSelect->method('where')
            ->willReturnMap(
                [
                    ['relation.parent_id = e.' . $linkField, null, null, $mainSelect],
                    ['table.attribute_id=?', $attributeId, null, $mainSelect],
                    ['table.store_id=?', 0, null, $mainSelect],
                    ['table.value=:targetrule_bind_0', null, null, $mainSelect]
                ]
            );
        $unionSelect->expects($this->once())
            ->method('union')
            ->with([$mainSelect, $childrenSelect])
            ->willReturnSelf();
        $this->attributesMock->method('getAttributeObject')
            ->willReturn($this->eavAttributeMock);
        $this->eavAttributeMock->expects($this->once())
            ->method('isScopeGlobal')
            ->willReturn(true);
        $this->eavAttributeMock->expects($this->once())
            ->method('isStatic')
            ->willReturn(false);
        $this->eavAttributeMock->method('getId')
            ->willReturn($attributeId);
        $this->eavAttributeMock->method('getBackendTable')
            ->willReturn($attributeTable);
        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($this->entityMetadataMock);
        $this->entityMetadataMock->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);
        $childrenSelect->method('from')
            ->willReturnSelf();
        $childrenSelect->method('where')
            ->willReturnMap(
                [
                    ["table.$linkField = e.$linkField", null, null, $childrenSelect],
                    ['table.attribute_id=?', $attributeId, null, $childrenSelect],
                    ['table.store_id=?', 0, null, $childrenSelect],
                    ["table.value={$attributesValue}", null, null, $childrenSelect]
                ]
            );
        $resultClause = $this->sqlBuilder->generateWhereClause(
            $this->attributesMock,
            $bind
        );
        $this->assertEquals("EXISTS ()", $resultClause);
    }

    /**
     * Tests generating WHERE clause for non-global scope.
     *
     * @return void
     */
    public function testGenerateWhereClauseForNonGlobalScopeAttribute(): void
    {
        $storeId = 1;
        $attributeId = 42;
        $attributesValue = 'string';
        $attributesOperator = '==';
        $attributeTable = 'catalog_product_entity_varchar';
        $this->attributesMock->setOperator($attributesOperator);
        $this->attributesMock->setAttribute('filter');
        $this->attributesMock->setValue($attributesValue);
        $entityFieldName = 'entity_id';
        $bind = [];
        $checkSql = 'IF(attr_s.value_id > 0, attr_s.value, attr_d.value)';
        $leftJoinSql = "attr_s.{$entityFieldName} = attr_d.{$entityFieldName}" .
            " AND attr_s.attribute_id = attr_d.attribute_id AND attr_s.store_id=?";

        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($this->entityMetadataMock);

        $this->entityMetadataMock->expects($this->once())
            ->method('getLinkField')
            ->willReturn($entityFieldName);

        $this->eavAttributeMock->expects($this->once())
            ->method('isScopeGlobal')
            ->willReturn(false);

        $this->indexResourceMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->attributesMock->expects($this->any())
            ->method('getAttributeObject')
            ->willReturn($this->eavAttributeMock);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->connectionMock->expects($this->once())
            ->method('getCheckSql')
            ->willReturn($checkSql);
        $this->connectionMock->expects($this->once())
            ->method('quoteInto')
            ->with($leftJoinSql, $storeId)
            ->willReturn($leftJoinSql);

        $this->eavAttributeMock->expects($this->once())
            ->method('isStatic')
            ->willReturn(false);
        $this->eavAttributeMock->expects($this->once())
            ->method('getId')
            ->willReturn($attributeId);
        $this->eavAttributeMock->expects($this->once())
            ->method('getBackendTable')
            ->willReturn($attributeTable);

        $this->indexResourceMock->expects($this->once())
            ->method('getOperatorCondition')
            ->with($checkSql, $attributesOperator, $attributesValue)
            ->willReturn($checkSql);

        $this->selectMock->expects($this->once())
            ->method('from')
            ->with(
                ['attr_d' => $attributeTable],
                'COUNT(*)'
            )
            ->willReturnSelf();

        $this->selectMock->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['attr_s' => $attributeTable],
                $leftJoinSql,
                []
            )
            ->willReturnSelf();
        $this->selectMock->expects($this->exactly(4))
            ->method('where')
            ->willReturnMap(
                [
                    ["attr_d.{$entityFieldName} = e.entity_id",null, null, $this->selectMock],
                    ['attr_d.attribute_id=?', $attributeId, null, $this->selectMock],
                    ["attr_d.storeId=?", Store::DEFAULT_STORE_ID, null, $this->selectMock],
                    [$checkSql, null, null, $this->selectMock]
                ]
            );

        $resultClause = $this->sqlBuilder->generateWhereClause(
            $this->attributesMock,
            $bind,
            $storeId
        );

        $this->assertEquals("() > 0", $resultClause);
    }

    /**
     * Tests condition operator mapping.
     *
     * @param string $conditionOperator
     * @param string $expectedOperator
     *
     * @return void
     * @dataProvider getCategoryIdsConditionOperatorDataProvider
     */
    public function testGetCategoryIdsConditionOperator(string $conditionOperator, string $expectedOperator): void
    {
        $mappedOperator = $this->sqlBuilder->getCategoryIdsConditionOperator($conditionOperator);

        $this->assertEquals($expectedOperator, $mappedOperator);
    }

    /**
     * @return array
     */
    public function getCategoryIdsConditionOperatorDataProvider(): array
    {
        return [
            ['!{}', '!()'],
            ['!=', '!()'],
            ['{}', '()'],
            ['==', '()'],
            ['()', '()'],
            ['!()', '!()']
        ];
    }
}
