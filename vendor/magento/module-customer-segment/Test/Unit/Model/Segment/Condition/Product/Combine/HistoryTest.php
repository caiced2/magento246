<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Product\Combine;

use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\History;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\ResourceModel\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test of Product History condition model.
 */
class HistoryTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var History
     */
    protected $subject;

    /**
     * Sales resource model mock.
     *
     * @var Order|MockObject
     */
    protected $resourceOrder;

    /**
     * Segment resource model mock.
     *
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment|MockObject
     */
    protected $resourceSegment;

    /**
     * Database adapter mock.
     *
     * @var Mysql|MockObject
     */
    protected $connectionMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->resourceOrder = $this->createPartialMock(
            Order::class,
            ['getConnection']
        );

        $this->connectionMock = $this->createPartialMock(
            Mysql::class,
            ['fetchOne', 'fetchCol']
        );

        $this->connectionMock->expects($this->any())->method('fetchCol')->willReturn([1, 2, 3]);

        $this->resourceOrder->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);

        $this->resourceSegment = $this->createPartialMock(
            Segment::class,
            ['createSelect', 'getTable', 'getConnection']
        );

        $select = $this->createPartialMock(
            Select::class,
            ['from', 'where', 'limit', 'reset', 'columns', '__toString']
        );

        $select->expects($this->any())->method('from')->willReturnSelf();
        $select->expects($this->any())->method('where')->willReturnSelf();
        $select->expects($this->any())->method('limit')->willReturnSelf();

        $this->resourceSegment->expects($this->any())->method('createSelect')->willReturn($select);
        $this->resourceSegment->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);

        $this->subject = $objectManager->getObject(
            History::class,
            [
                'resourceOrder' => $this->resourceOrder,
                'resourceSegment' => $this->resourceSegment
            ]
        );
    }

    /**
     * @param bool $isSatisfied
     * @param string $operator
     * @param bool $expected
     * @dataProvider isSatisfiedByDataProvider
     * @return void
     */
    public function testIsSatisfiedBy($isSatisfied, $operator, $expected)
    {
        $this->connectionMock->expects($this->once())->method('fetchOne')->willReturn($isSatisfied);
        $this->subject->setOperator($operator);
        $this->assertEquals($expected, $this->subject->isSatisfiedBy(1, 1, []));

        $this->assertEquals([1, 2, 3], $this->subject->getData('product_ids'));
    }

    /**
     * @return array
     */
    public function isSatisfiedByDataProvider()
    {
        return [
            [1, '!=', false],
            [1, '==', true],
            [0, '!=', true],
            [0, '==', false],
        ];
    }

    public function testGetSatisfiedIds()
    {
        $this->assertEquals([1, 2, 3], $this->subject->getSatisfiedIds(1));
        $this->assertEquals([1, 2, 3], $this->subject->getData('product_ids'));
    }

    /**
     * @param bool $smartMode
     * @param string|null $conditionValue
     * @param string $expectedResource
     * @dataProvider getResourceDataProvider
     * @return void
     */
    public function testGetResource($smartMode, $conditionValue, $expectedResource)
    {
        $this->subject->setValue($conditionValue);

        if ($expectedResource == 'segment') {
            $this->assertEquals($this->resourceSegment, $this->subject->getResource($smartMode));
        } else {
            $this->assertEquals($this->resourceOrder, $this->subject->getResource($smartMode));
        }
    }

    /**
     * @return array
     */
    public function getResourceDataProvider()
    {
        return [
            [false, null, 'segment'],
            [true, History::VIEWED, 'segment'],
            [true, History::ORDERED, 'order']
        ];
    }
}
