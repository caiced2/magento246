<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Actions\Condition\Product\Attributes;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes\SqlBuilder;
use Magento\TargetRule\Model\ResourceModel\Index;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIfNullSql', 'getCheckSql'])
            ->getMockForAbstractClass();

        $this->selectMock = $this->createPartialMock(
            Select::class,
            ['from', 'assemble', 'where', 'joinLeft']
        );
        $this->metadataPoolMock = $this->createPartialMock(
            MetadataPool::class,
            ['getMetadata']
        );
        $this->eavAttributeMock = $this->createPartialMock(
            Attribute::class,
            ['isScopeGlobal', 'isStatic', 'getBackendTable', 'getId']
        );
        $this->attributesMock = $this->getMockBuilder(Attributes::class)
            ->addMethods(['getValueType'])
            ->onlyMethods(['getAttributeObject'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sqlBuilder = new SqlBuilder($this->metadataPoolMock, $this->indexResourceMock);
    }

    public function testGenerateWhereClauseForStaticAttribute()
    {
        $attributesValue = '1,2';
        $attributesNormalizedValue = [1,2];
        $attributesOperator = '()';
        $attribute = 'filter';
        $bind = [];
        $expectedClause = "e.row_id IN (1,2)";
        $this->attributesMock->setOperator($attributesOperator);
        $this->attributesMock->setAttribute($attribute);
        $this->attributesMock->setValue($attributesValue);

        $this->eavAttributeMock->expects($this->once())
            ->method('isStatic')
            ->willReturn(true);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->indexResourceMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->indexResourceMock->expects($this->once())
            ->method('getOperatorCondition')
            ->with('e.' . $attribute, $attributesOperator, $attributesNormalizedValue)
            ->willReturn($expectedClause);

        $this->attributesMock->expects($this->any())
            ->method('getAttributeObject')
            ->willReturn($this->eavAttributeMock);
        $this->attributesMock->expects($this->exactly(2))
            ->method('getValueType')
            ->willReturn(Attributes::VALUE_TYPE_CONSTANT);

        $resultClause = $this->sqlBuilder->generateWhereClause(
            $this->attributesMock,
            $bind
        );

        $this->assertEquals("({$expectedClause})", $resultClause);
    }

    public function testGenerateWhereClauseForCategoryIds()
    {
        $attributesValue = '1,2';
        $attributesOperator = '()';
        $attribute = 'category_ids';
        $bind = [];
        $categoryTable = 'catalog_category_product';
        $categoryWhere = 'category_id in (1,2)';
        $this->attributesMock->setOperator($attributesOperator);
        $this->attributesMock->setAttribute($attribute);
        $this->attributesMock->setValue($attributesValue);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->indexResourceMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->indexResourceMock->expects($this->atLeastOnce())
            ->method('getTable')
            ->with('catalog_category_product')
            ->willReturn($categoryTable);
        $this->indexResourceMock->expects($this->once())
            ->method('getOperatorBindCondition')
            ->with(
                'category_id',
                'category_ids',
                $attributesOperator,
                $bind,
                ['bindArrayOfIds']
            )->willReturn($categoryWhere);

        $this->attributesMock->expects($this->any())
            ->method('getAttributeObject')
            ->willReturn($this->eavAttributeMock);
        $this->attributesMock->expects($this->once())
            ->method('getValueType')
            ->willReturn(Attributes::VALUE_TYPE_SAME_AS);

        $this->selectMock->expects($this->once())
            ->method('from')
            ->with($categoryTable, 'COUNT(*)')
            ->willReturnSelf();
        $this->selectMock->expects($this->exactly(2))
            ->method('where')
            ->willReturnMap([
                ['product_id=e.entity_id', null, null, $this->selectMock],
                [$categoryWhere, null, null, $this->selectMock]
            ]);
        $this->selectMock->expects($this->once())
            ->method('assemble')
            ->willReturn($categoryWhere);

        $resultClause = $this->sqlBuilder->generateWhereClause(
            $this->attributesMock,
            $bind
        );
        $this->assertEquals("({$categoryWhere}) > 0", $resultClause);
    }
}
