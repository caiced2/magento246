<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Customer\Address;

use Magento\Customer\Model\ResourceModel\Address;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\Attributes;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var Attributes
     */
    protected $subject;

    /**
     * @var Segment|MockObject
     */
    protected $resourceSegmentMock;

    /**
     * @var Address|MockObject
     */
    protected $resourceAddressMock;

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

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->resourceSegmentMock = $this->createPartialMock(
            Segment::class,
            ['createSelect', 'createConditionSql']
        );

        $this->selectMock = $this->createPartialMock(Select::class, ['from', 'where', 'limit']);

        $this->resourceSegmentMock->expects($this->any())
            ->method('createSelect')
            ->willReturn($this->selectMock);

        $this->resourceAddressMock = $this->createPartialMock(
            Address::class,
            ['loadAllAttributes']
        );

        $eavEntity = $this->getMockForAbstractClass(
            AbstractEntity::class,
            [],
            '',
            false,
            false,
            true,
            ['getAttributesByCode']
        );

        $eavEntity->expects($this->any())
            ->method('getAttributesByCode')
            ->willReturn([]);

        $this->resourceAddressMock->expects($this->any())
            ->method('loadAllAttributes')
            ->willReturn($eavEntity);

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
            ->willReturn('country');

        $this->eavConfigMock->expects($this->any())
            ->method('getAttribute')
            ->with('customer_address', 'country')
            ->willReturn($this->attributeMock);

        $this->subject = $objectManager->getObject(
            Attributes::class,
            [
                'resourceSegment' => $this->resourceSegmentMock,
                'resourceAddress' => $this->resourceAddressMock,
                'eavConfig' => $this->eavConfigMock
            ]
        );

        $this->subject->setData('attribute', 'country');
        $this->subject->setData('operator', '==');
        $this->subject->setData('value', 'US');
    }

    /**
     * @param bool $customer
     * @param bool $isFiltered
     * @param bool|null $isStaticAttribute
     *
     * @return void
     * @dataProvider getConditionsSqlDataProvider
     */
    public function testGetConditionsSql(bool $customer, bool $isFiltered, ?bool $isStaticAttribute): void
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
            $this->attributeMock->expects($this->any())
                ->method('isStatic')
                ->willReturn($isStaticAttribute);

            $this->selectMock->expects($this->once())
                ->method('from')
                ->with(['val' => 'data_table'], [new \Zend_Db_Expr(1)]);

            $column = $isStaticAttribute ? "`val`.`country`" : "`val`.`value`";
            $condition = "$column = 'US'";

            $this->resourceSegmentMock->expects($this->any())
                ->method('createConditionSql')
                ->with($column, '==', 'US')
                ->willReturn($condition);

            if (!$isStaticAttribute) {
                $this->selectMock
                    ->method('where')
                    ->withConsecutive(
                        ["`val`.`attribute_id` = ?", 1],
                        ["`val`.`entity_id` = `customer_address`.`entity_id`"],
                        [$condition]
                    );
            } else {
                $this->selectMock
                    ->method('where')
                    ->withConsecutive(
                        ["`val`.`entity_id` = `customer_address`.`entity_id`"],
                        [$condition]
                    );
            }

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
    public function getConditionsSqlDataProvider(): array
    {
        return [
            [false, true, null],
            [true, true, true],
            [true, false, true],
            [true, false, false]
        ];
    }
}
