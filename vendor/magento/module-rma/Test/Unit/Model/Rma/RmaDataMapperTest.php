<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Rma;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Rma\Model\Item;
use Magento\Rma\Model\ResourceModel\Item\Collection;
use Magento\Rma\Model\ResourceModel\Item\CollectionFactory;
use Magento\Rma\Model\Rma\RmaDataMapper;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RmaDataMapperTest extends TestCase
{
    /** @var RmaDataMapper */
    protected $rmaDataMapper;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var MockObject */
    protected $dateTimeFactoryMock;

    /** @var MockObject */
    protected $collectionFactoryMock;

    protected function setUp(): void
    {
        $this->dateTimeFactoryMock = $this->createPartialMock(
            DateTimeFactory::class,
            ['create']
        );
        $this->collectionFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->rmaDataMapper = $this->objectManagerHelper->getObject(
            RmaDataMapper::class,
            [
                'dateTimeFactory' => $this->dateTimeFactoryMock,
                'itemCollectionFactory' => $this->collectionFactoryMock
            ]
        );
    }

    public function testFilterRmaSaveRequestException()
    {
        $saveRequest = [];

        $this->expectException(LocalizedException::class);

        $this->rmaDataMapper->filterRmaSaveRequest($saveRequest);
    }

    public function testFilterRmaSaveRequest()
    {
        $items = [
            0 => ['item'],
            1 => ['qty_authorized' => 5],
            'new1' => ['qty_authorized' => 5],
        ];
        $expectedItems = [
            1 => ['qty_authorized' => 5, 'entity_id' => 1],
            'new1' => ['qty_authorized' => 5, 'entity_id' => null],
        ];

        $this->assertEquals(
            ['items' => $expectedItems],
            $this->rmaDataMapper->filterRmaSaveRequest(['items' => $items])
        );
    }

    /**
     * @dataProvider saveRequestEmailDataProvider
     * @param array $saveRequest
     * @param string $emailExpectation
     */
    public function testPrepareNewRmaInstanceData($saveRequest, $emailExpectation)
    {
        $dateMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getId',
                    'getIncrementId',
                    'getStoreId',
                    'getCustomerId',
                    'getCreatedAt',
                    'getCustomerName',
                    '__wakeup',
                ]
            )
            ->getMock();
        $expectedRmaData = [
            'status' => Status::STATE_PENDING,
            'date_requested' => '2038-00-00 00:00:00',
            'order_id' => '1',
            'order_increment_id' => '1000101',
            'store_id' => '7',
            'customer_id' => '5',
            'order_date' => '2037-00-00 00:00:00',
            'customer_name' => 'Brian',
            'customer_custom_email' => $emailExpectation,
        ];

        $this->dateTimeFactoryMock->expects($this->once())->method('create')
            ->willReturn($dateMock);
        $dateMock->expects($this->once())->method('gmtDate')
            ->willReturn($expectedRmaData['date_requested']);
        $orderMock->expects($this->once())->method('getId')
            ->willReturn($expectedRmaData['order_id']);
        $orderMock->expects($this->once())->method('getIncrementId')
            ->willReturn($expectedRmaData['order_increment_id']);
        $orderMock->expects($this->once())->method('getStoreId')
            ->willReturn($expectedRmaData['store_id']);
        $orderMock->expects($this->once())->method('getCustomerId')
            ->willReturn($expectedRmaData['customer_id']);
        $orderMock->expects($this->once())->method('getCreatedAt')
            ->willReturn($expectedRmaData['order_date']);
        $orderMock->expects($this->once())->method('getCustomerName')
            ->willReturn($expectedRmaData['customer_name']);

        $this->assertEquals(
            $expectedRmaData,
            $this->rmaDataMapper->prepareNewRmaInstanceData($saveRequest, $orderMock)
        );
    }

    public function testCombineItemStatuses()
    {
        $rmaId = 1;
        $requestedItems = [
            0 => [],
            1 => ['status' => 'awful'],
        ];
        $expectedStatuses = [
            'awful',
            'pending_to_be_awful',
        ];
        $itemCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getStatus'])
            ->getMock();

        $this->collectionFactoryMock->expects($this->once())->method('create')
            ->willReturn($itemCollection);
        $itemCollection->expects($this->once())->method('addAttributeToFilter')
            ->with('rma_entity_id', $rmaId);
        $itemCollection->expects($this->once())->method('getIterator')
            ->willReturn(new \ArrayIterator([$itemMock]));
        $itemMock->expects($this->once())->method('getId')->willReturn(2);
        $itemMock->expects($this->once())->method('getStatus')->willReturn('pending_to_be_awful');

        $this->assertEquals($expectedStatuses, $this->rmaDataMapper->combineItemStatuses($requestedItems, $rmaId));
    }

    /**
     * @return array
     */
    public function saveRequestEmailDataProvider()
    {
        return [
            [[], ''],
            [['contact_email' => 'learnpython.org'], 'learnpython.org']
        ];
    }
}
