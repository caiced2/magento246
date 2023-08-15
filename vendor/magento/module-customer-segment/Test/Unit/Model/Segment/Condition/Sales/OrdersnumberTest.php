<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Sales;

use Magento\CustomerSegment\Model\ConditionFactory;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Daterange;
use Magento\CustomerSegment\Model\Segment\Condition\Order\Status;
use Magento\CustomerSegment\Model\Segment\Condition\Sales\Ordersnumber;
use Magento\CustomerSegment\Model\Segment\Condition\Uptodate;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Layout;
use Magento\Rule\Model\Condition\Context;
use Magento\Sales\Model\ResourceModel\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend_Db_Expr;

/**
 * Tests order numbers condition
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrdersnumberTest extends TestCase
{
    /**
     * @var Ordersnumber
     */
    protected $model;

    /**
     * @var Order|MockObject
     */
    protected $orderResourceMock;

    /**
     * @var ConditionFactory|MockObject
     */
    protected $conditionFactoryMock;

    /**
     * @var Segment|MockObject
     */
    protected $resourceSegment;

    /**
     * @var string
     */
    protected $salesOrderTable = 'sales_order';

    /**
     * @var string
     */
    protected $storeTable = 'store';

    /**
     * @var array
     */
    protected $storeIds = [1];

    /**
     * @var Layout|MockObject
     */
    protected $layout;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->orderResourceMock = $this->createMock(Order::class);
        $this->layout = $this->createMock(Layout::class);
        $ruleContextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ruleContextMock->method('getLayout')->willReturn($this->layout);
        $this->resourceSegment =
            $this->createMock(Segment::class);

        $this->conditionFactoryMock = $this->createMock(ConditionFactory::class);

        $this->model = $objectManager->getObject(
            Ordersnumber::class,
            [
                'context' => $ruleContextMock,
                'orderResource' => $this->orderResourceMock,
                'conditionFactory' => $this->conditionFactoryMock,
                'resourceSegment' => $this->resourceSegment
            ]
        );
    }

    /**
     * Test get new child select options
     */
    public function testGetNewChildSelectOptions()
    {
        $orderStatusOption = 'Order Status';
        $upToDateOption = 'Up To Date';
        $dateRangeOption = 'Date Range';

        $orderStatusMock = $this->createMock(Status::class);
        $orderStatusMock->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($orderStatusOption);
        $upToDateMock = $this->createMock(Uptodate::class);
        $upToDateMock->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($upToDateOption);
        $dateRangeMock = $this->createMock(Daterange::class);
        $dateRangeMock->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($dateRangeOption);

        $returnValueMap = [
            ['Order\Status', [], $orderStatusMock],
            ['Uptodate', [], $upToDateMock],
            ['Daterange', [], $dateRangeMock]
        ];

        $this->conditionFactoryMock->method('create')
            ->willReturnMap($returnValueMap);

        $expectedResult = [
            [
                'value' => '',
                'label' => __('Please choose a condition to add.')
            ],
            $orderStatusOption,
            [
                'value' => [
                    $upToDateOption,
                    $dateRangeOption,
                ],
                'label' => __('Date Ranges')
            ]
        ];
        $this->assertEquals($expectedResult, $this->model->getNewChildSelectOptions());
    }

    /**
     * Test load attribute options
     */
    public function testLoadAttributeOptions()
    {
        $this->assertEquals($this->model, $this->model->loadAttributeOptions());
        $this->assertEquals(['total' => __('Total'), 'average' => __('Average')], $this->model->getAttributeOption());
    }

    /**
     * Test get value element type
     */
    public function testGetValueElementType()
    {
        $this->assertEquals('text', $this->model->getValueElementType());
    }

    /**
     * Test get matched events
     */
    public function testGetMatchedEvents()
    {
        $this->assertEquals(['sales_order_save_commit_after'], $this->model->getMatchedEvents());
    }

    /**
     * Test load value options
     */
    public function testLoadValueOptions()
    {
        $this->assertEquals($this->model, $this->model->loadValueOptions());
        $this->assertEquals([], $this->model->getValueOption());
    }

    /**
     * Test getConditionsSql() when zero does not match the condition
     *
     * @dataProvider getConditionsSqlDataProvider
     */
    public function testGetConditionsSql($operator, $value, $attribute, $checkSql)
    {
        $website = 1;
        $salesOrderTable = 'sales_order_table';
        $storeTable = 'store_table';
        $checkSqlResult = 'check_sql_result';
        $storeIds = [1, 2];

        $this->model->setData('operator', $operator);
        $this->model->setData('value', $value);
        $this->model->setData('attribute', $attribute);

        $select = $this->createMock(Select::class);
        $select->expects($this->once())
            ->method('from')
            ->with(['sales_order' => $salesOrderTable], ['sales_order.customer_id'])
            ->willReturnSelf();

        $select->expects($this->once())
            ->method('group')
            ->with(['sales_order.customer_id'])
            ->willReturnSelf();

        $select->expects($this->once())
            ->method('having')
            ->with(new Zend_Db_Expr($checkSqlResult))
            ->willReturnSelf();
        $select->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['sales_order.customer_id IS NOT NULL'],
                ['sales_order.store_id IN (?)', $storeIds]
            )
            ->willReturnSelf();

        $storeSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeSelect->expects($this->once())
            ->method('from')
            ->with(['store' => $storeTable], ['store.store_id'])
            ->willReturnSelf();
        $storeSelect->expects($this->once())
            ->method('where')
            ->with('store.website_id IN (?)', $website)
            ->willReturnSelf();

        $this->resourceSegment->expects($this->exactly(2))
            ->method('createSelect')
            ->willReturnOnConsecutiveCalls($select, $storeSelect);

        $this->resourceSegment->expects($this->once())
            ->method('getSqlOperator')
            ->willReturn($operator);

        $connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resourceSegment->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('fetchCol')
            ->with($storeSelect)
            ->willReturn($storeIds);
        $connection->expects($this->once())
            ->method('quote')
            ->with((double) $value)
            ->willReturn((double) $value);
        //for getConditionSql()
        $connection->expects($this->once())
            ->method('getCheckSql')
            ->with($checkSql, 1, 0)
            ->willReturn($checkSqlResult);

        $this->resourceSegment->expects($this->exactly(2))
            ->method('getTable')
            ->willReturnMap([['sales_order', $salesOrderTable], ['store', $storeTable]]);

        $this->assertEquals($select, $this->model->getConditionsSql(null, 1, false));
    }

    /**
     * @return array
     */
    public function getConditionsSqlDataProvider()
    {
        return [
            ['==', 1, 'total', 'COUNT(*) == 1'],
            ['!=', 0, 'total', 'COUNT(*) != 0'],
            ['>=', 1, 'total', 'COUNT(*) >= 1'],
            ['>', 0, 'total', 'COUNT(*) > 0'],
        ];
    }

    /**
     * @param $operator
     * @param $select
     * @param $connection
     */
    protected function stepResourceSegmentPreparation($operator, $select, $connection)
    {
        $this->resourceSegment->expects($this->atLeastOnce())->method('createSelect')->willReturn($select);
        $this->resourceSegment->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($connection);
        $this->resourceSegment->expects($this->once())->method('getSqlOperator')
            ->with($operator)
            ->willReturn($operator);
        $this->resourceSegment->expects($this->exactly(2))
            ->method('getTable')
            ->withConsecutive(
                [$this->salesOrderTable],
                [$this->storeTable]
            )->willReturnOnConsecutiveCalls($this->salesOrderTable, $this->storeTable);
    }

    /**
     * @param $operator
     * @param $value
     * @param $select
     * @return MockObject
     */
    protected function stepSegmentAdapterPreparation($operator, $value, $select)
    {
        $connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $connection->expects($this->once())->method('quote')->willReturn($value);
        $connection->expects($this->once())->method('getCheckSql')
            ->with("COUNT(*) $operator $value", 1, 0)
            ->willReturn("COUNT(*) $operator $value");
        $connection->expects($this->once())->method('fetchCol')->with($select)->willReturn($this->storeIds);
        return $connection;
    }

    /**
     * @return MockObject
     */
    protected function stepOrderAdapterPreparation()
    {
        $orderAdapter = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->orderResourceMock->expects($this->once())->method('getConnection')
            ->willReturn($orderAdapter);
        return $orderAdapter;
    }

    /**
     * @param $operator
     * @param $value
     * @param $select
     */
    protected function stepResourcesExpects($operator, $value, $select)
    {
        $this->stepResourceSegmentPreparation(
            $operator,
            $select,
            $this->stepSegmentAdapterPreparation($operator, $value, $select)
        );
        $this->model->setData('operator', $operator);
        $this->model->setData('value', $value);
    }
}
