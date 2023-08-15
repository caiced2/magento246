<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Customer\Address;

use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\Region;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegionTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var Region
     */
    protected $subject;

    /**
     * @var Segment|MockObject
     */
    protected $resourceSegmentMock;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $connectionMock;

    /**
     * @var Attribute|MockObject
     */
    protected $attributeMock;

    /**
     * @var Config|MockObject
     */
    protected $eavConfigMock;

    /**
     * @var Select|MockObject
     */
    protected $selectMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->resourceSegmentMock = $this->createPartialMock(
            Segment::class,
            ['createSelect', 'getConnection']
        );

        $this->selectMock = $this->createPartialMock(Select::class, ['from', 'where', 'limit']);

        $this->resourceSegmentMock->expects($this->any())
            ->method('createSelect')
            ->willReturn($this->selectMock);

        $this->connectionMock = $this->getMockForAbstractClass(
            AdapterInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getCheckSql']
        );

        $this->resourceSegmentMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->eavConfigMock = $this->createPartialMock(Config::class, ['getAttribute']);

        $this->attributeMock = $this->createPartialMock(
            Attribute::class,
            ['isStatic', 'getBackendTable', 'getAttributeCode', 'getId']
        );

        $this->attributeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->attributeMock->expects($this->any())
            ->method('getBackendTable')
            ->willReturn('data_table');

        $this->attributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('region');

        $this->eavConfigMock->expects($this->any())
            ->method('getAttribute')
            ->with('customer_address', 'region')
            ->willReturn($this->attributeMock);

        $this->subject = $objectManager->getObject(
            Region::class,
            [
                'resourceSegment' => $this->resourceSegmentMock,
                'eavConfig' => $this->eavConfigMock
            ]
        );

        $this->subject->setData('value', '1');
    }

    /**
     * @param bool $customer
     * @param bool $isFiltered
     * @dataProvider getConditionsSqlDataProvider
     * @return void
     */
    public function testGetConditionsSql($customer, $isFiltered)
    {
        if (!$customer && $isFiltered) {
            $this->selectMock->expects($this->once())
                ->method('from')
                ->with(['' => new \Zend_Db_Expr('dual')], [new \Zend_Db_Expr(0)]);

            $this->selectMock->expects($this->never())
                ->method('where');

            $this->selectMock->expects($this->never())
                ->method('limit');
        } else {
            $column = 'region';
            $isNullCondition = "IF(caev.region IS NULL, 0, 1)";

            $this->connectionMock->expects($this->once())
                ->method('getCheckSql')
                ->with("caev.$column IS NULL", 0, 1)
                ->willReturn($isNullCondition);

            $this->selectMock->expects($this->once())
                ->method('from')
                ->with(['caev' => 'data_table'], $isNullCondition)
                ->willReturnSelf();

            $this->selectMock->expects($this->once())
                ->method('where')
                ->with("caev.entity_id = customer_address.entity_id");

            if ($isFiltered) {
                $this->selectMock->expects($this->once())
                    ->method('limit')
                    ->with(1);
            }
        }

        $this->subject->getConditionsSql($customer, 1, $isFiltered);
    }

    /**
     * @return array
     */
    public function getConditionsSqlDataProvider()
    {
        return [
            [false, true],
            [true, true],
            [true, false]
        ];
    }
}
