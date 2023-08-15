<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\ResourceModel\Sales;

use Magento\Customer\Model\Attribute;
use Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote;
use Magento\CustomerCustomAttributes\Model\Sales\AbstractSales;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteTest extends TestCase
{
    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceMock;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote|MockObject
     */
    protected $parentResourceModelMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->parentResourceModelMock = $this->createMock(\Magento\Quote\Model\ResourceModel\Quote::class);

        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->willReturnArgument(0);

        $contextMock = $this->createMock(Context::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->resourceMock);

        $this->quote = new Quote(
            $contextMock,
            $this->parentResourceModelMock
        );
    }

    /**
     * @param string $backendType
     * @dataProvider dataProviderSaveNewAttributeNegative
     */
    public function testSaveNewAttributeNegative($backendType)
    {
        $attributeMock = $this->createMock(Attribute::class);
        $attributeMock->expects($this->once())
            ->method('getBackendType')
            ->willReturn($backendType);

        $this->connectionMock->expects($this->never())
            ->method('addColumn');

        $this->assertEquals($this->quote, $this->quote->saveNewAttribute($attributeMock));
    }

    /**
     * @return array
     */
    public function dataProviderSaveNewAttributeNegative()
    {
        return [
            [''],
            [Attribute::TYPE_STATIC],
            ['something_wrong'],
        ];
    }

    /**
     * @param string $backendType
     * @param array $definition
     * @dataProvider dataProviderSaveNewAttribute
     */
    public function testSaveNewAttribute($backendType, array $definition)
    {
        $attributeMock = $this->createMock(Attribute::class);
        $attributeMock->expects($this->once())
            ->method('getBackendType')
            ->willReturn($backendType);
        $attributeMock->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('attribute_code');

        $definition['comment'] = 'Customer Attribute Code';

        $this->connectionMock->expects($this->once())
            ->method('addColumn')
            ->with('magento_customercustomattributes_sales_flat_quote', 'customer_attribute_code', $definition, null);

        $this->assertEquals($this->quote, $this->quote->saveNewAttribute($attributeMock));
    }

    /**
     * @return array
     */
    public function dataProviderSaveNewAttribute()
    {
        return [
            ['datetime', ['type' => Table::TYPE_DATE]],
            ['decimal', ['type' => Table::TYPE_DECIMAL, 'length' => '12,4']],
            ['int', ['type' => Table::TYPE_INTEGER]],
            ['text', ['type' => Table::TYPE_TEXT]],
            ['varchar', ['type' => Table::TYPE_TEXT, 'length' => 255]],
        ];
    }

    public function testDeleteAttribute()
    {
        $attributeMock = $this->createMock(Attribute::class);
        $attributeMock->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('attribute_code');

        $this->connectionMock->expects($this->once())
            ->method('dropColumn')
            ->with('magento_customercustomattributes_sales_flat_quote', 'customer_attribute_code', null);

        $this->assertEquals($this->quote, $this->quote->deleteAttribute($attributeMock));
    }

    public function testIsEntityExistsNoId()
    {
        $salesMock = $this->createMock(AbstractSales::class);
        $salesMock->expects($this->once())
            ->method('getId')
            ->willReturn(0);

        $this->connectionMock->expects($this->never())
            ->method('select');
        $this->connectionMock->expects($this->never())
            ->method('fetchOne');

        $this->assertFalse($this->quote->isEntityExists($salesMock));
    }

    /**
     * @param string $fetchedColumn
     * @param bool $result
     * @dataProvider dataProviderIsEntityExists
     */
    public function testIsEntityExists($fetchedColumn, $result)
    {
        $salesMock = $this->createMock(AbstractSales::class);
        $salesMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);

        $selectMock = $this->createMock(Select::class);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);

        $this->parentResourceModelMock->expects($this->once())
            ->method('getMainTable')
            ->willReturn('parent_table');
        $this->parentResourceModelMock->expects($this->once())
            ->method('getIdFieldName')
            ->willReturn('parent_id');

        $selectMock->expects($this->once())
            ->method('from')
            ->with('parent_table', 'parent_id')->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('where')
            ->with("parent_id = ?", 1)
            ->willReturnSelf();

        $this->connectionMock->expects($this->once())
            ->method('fetchOne')
            ->with($selectMock)
            ->willReturn($fetchedColumn);

        $this->assertEquals($result, $this->quote->isEntityExists($salesMock));
    }

    /**
     * @return array
     */
    public function dataProviderIsEntityExists()
    {
        return [
            ['', false],
            ['some_value', true],
        ];
    }
}
