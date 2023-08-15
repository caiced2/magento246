<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Order\Address;

use Magento\CustomerSegment\Model\ConditionFactory;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Order\Address\Combine;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Rule\Model\Condition\Context;
use Magento\Sales\Model\ResourceModel\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CombineTest extends TestCase
{
    /**
     * @var Combine
     */
    protected $model;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var Segment|MockObject
     */
    protected $resourceSegment;

    /**
     * @var Order|MockObject
     */
    protected $resourceOrder;

    /**
     * @var ConditionFactory|MockObject
     */
    protected $conditionFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegment =
            $this->createMock(Segment::class);
        $this->resourceOrder = $this->createMock(Order::class);
        $this->conditionFactory = $this->createMock(ConditionFactory::class);
        $this->model = new Combine(
            $this->context,
            $this->conditionFactory,
            $this->resourceSegment,
            $this->resourceOrder
        );
    }

    /**
     * @return void
     */
    public function testIsSatisfiedBy(): void
    {
        $table = 'sales_order';
        $tableAddress = 'sales_order_address';
        $select = $this->createMock(Select::class);
        $connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resourceSegment->expects($this->once())->method('createSelect')->willReturn($select);
        $this->resourceOrder
            ->method('getTable')
            ->withConsecutive(['sales_order'], ['sales_order_address'])
            ->willReturnOnConsecutiveCalls($table, $tableAddress);
        $select->expects($this->once())
            ->method('from')
            ->with(['order_address_order' => $table], [new \Zend_Db_Expr(1)])
            ->willReturn($select);
        $select->expects($this->once())
            ->method('where')
            ->with('order_address_order.customer_id = :customer_id')
            ->willReturn($select);
        $select->expects($this->once())
            ->method('join')
            ->with(
                ['order_address' => $tableAddress],
                'order_address.parent_id = order_address_order.entity_id',
                []
            )->willReturn($select);
        $select->expects($this->once())
            ->method('limit')
            ->with(1)
            ->willReturn($select);
        $this->resourceOrder->expects($this->atLeastOnce())->method('getConnection')->willReturn($connection);
        $connection->expects($this->once())->method('fetchOne')->willReturn(1);
        $this->assertTrue($this->model->isSatisfiedBy(1, 1, []));
    }
}
