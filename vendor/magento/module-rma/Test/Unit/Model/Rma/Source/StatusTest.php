<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Rma\Source;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Model\Item\Attribute\Source\Status as ItemAttributeStatus;
use Magento\Rma\Model\Item\Attribute\Source\StatusFactory;
use Magento\Rma\Model\Rma\Source\Status;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    /**
     * @var StatusFactory|MockObject
     */
    protected $statusFactoryMock;

    /**
     * @var MockObject
     */
    protected $attrOptionCollectionFactoryMock;

    /**
     * @var OptionFactory|MockObject
     */
    protected $attrOptionFactoryMock;

    /**
     * @var Status
     */
    protected $status;

    protected function setUp(): void
    {
        $this->statusFactoryMock = $this->createPartialMock(
            StatusFactory::class,
            ['create']
        );
        $this->attrOptionCollectionFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->attrOptionFactoryMock = $this->createPartialMock(
            OptionFactory::class,
            ['create']
        );
        $this->status = (new ObjectManager($this))->getObject(
            \Magento\Rma\Model\Rma\Source\Status::class,
            [
                'attrOptionCollectionFactory' => $this->attrOptionCollectionFactoryMock,
                'attrOptionFactory' => $this->attrOptionFactoryMock,
                'statusFactory' => $this->statusFactoryMock,
            ]
        );
    }

    /**
     * @dataProvider itemLabelDataProvider
     * @param string $state
     * @param string $expected
     */
    public function testGetItemLabel($state, $expected)
    {
        $this->assertEquals($expected, $this->status->getItemLabel($state));
    }

    /**
     * @dataProvider statusByItemsDataProvider
     * @param array $itemStatusArray
     * @param string $expected
     */
    public function testGetStatusByItems($itemStatusArray, $expected)
    {
        $itemStatusModelMock = $this->createMock(\Magento\Rma\Model\Item\Attribute\Source\Status::class);
        $this->statusFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemStatusModelMock);
        $itemStatusModelMock->expects($this->any())
            ->method('checkStatus')
            ->willReturn(true);

        $this->assertEquals($expected, $this->status->getStatusByItems($itemStatusArray));
    }

    /**
     * @dataProvider statusByItemsExceptionDataProvider
     * @param mixed $itemStatusArray
     */
    public function testGetStatusByItemsException($itemStatusArray)
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('This is the wrong RMA item status.');
        $itemStatusModelMock = $this->createMock(\Magento\Rma\Model\Item\Attribute\Source\Status::class);
        $this->statusFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($itemStatusModelMock);
        $itemStatusModelMock->expects($this->any())
            ->method('checkStatus')
            ->willReturn(false);

        $this->assertNull($this->status->getStatusByItems($itemStatusArray));
    }

    /**
     * @dataProvider buttonDisabledStatusDataProvider
     * @param string $status
     * @param bool $expected
     */
    public function testGetButtonDisabledStatus($status, $expected)
    {
        $this->assertEquals($expected, $this->status->getButtonDisabledStatus($status));
    }

    public function statusByItemsDataProvider()
    {
        return [
            [
                ['item1' => ItemAttributeStatus::STATE_PENDING, 'item2' => ItemAttributeStatus::STATE_PENDING],
                Status::STATE_PENDING,
            ],
            [
                ['item1' => ItemAttributeStatus::STATE_AUTHORIZED],
                Status::STATE_AUTHORIZED
            ],
            [
                ['item1' => ItemAttributeStatus::STATE_DENIED],
                Status::STATE_CLOSED],
            [
                ['item1' => ItemAttributeStatus::STATE_DENIED, 'item2' => ItemAttributeStatus::STATE_PENDING],
                Status::STATE_PENDING
            ],
            [
                ['item1' => ItemAttributeStatus::STATE_PENDING, 'item2' => ItemAttributeStatus::STATE_AUTHORIZED],
                Status::STATE_PARTIAL_AUTHORIZED
            ],
            [
                ['item1' => ItemAttributeStatus::STATE_RECEIVED],
                Status::STATE_RECEIVED
            ],
            [
                ['item1' => ItemAttributeStatus::STATE_RECEIVED, 'item2' => ItemAttributeStatus::STATE_PENDING],
                Status::STATE_RECEIVED_ON_ITEM
            ],
            [
                ['item1' => ItemAttributeStatus::STATE_APPROVED],
                Status::STATE_PROCESSED_CLOSED
            ],
            [
                ['item1' => ItemAttributeStatus::STATE_DENIED, 'item2' => ItemAttributeStatus::STATE_APPROVED],
                Status::STATE_PROCESSED_CLOSED

            ],
            [
                ['item1' => ItemAttributeStatus::STATE_PENDING, 'item2' => ItemAttributeStatus::STATE_APPROVED],
                Status::STATE_APPROVED_ON_ITEM
            ],
            [
                ['item1' => ItemAttributeStatus::STATE_REJECTED],
                Status::STATE_CLOSED
            ],
            [
                ['item1' => ItemAttributeStatus::STATE_DENIED, 'item2' => ItemAttributeStatus::STATE_REJECTED],
                Status::STATE_CLOSED
            ],
            [
                [
                    'item1' => ItemAttributeStatus::STATE_PENDING, 'item2' => ItemAttributeStatus::STATE_REJECTED,
                ],
                Status::STATE_REJECTED_ON_ITEM
            ],
            [
                [
                    'item1' => ItemAttributeStatus::STATE_APPROVED, 'item2' => ItemAttributeStatus::STATE_REJECTED,
                ],
                Status::STATE_PROCESSED_CLOSED
            ],
            [
                [
                    'item1' => ItemAttributeStatus::STATE_APPROVED, 'item2' => ItemAttributeStatus::STATE_REJECTED,
                    'item3' => ItemAttributeStatus::STATE_DENIED,
                ],
                Status::STATE_PROCESSED_CLOSED
            ],
            [
                [
                    'item1' => ItemAttributeStatus::STATE_APPROVED, 'item2' => ItemAttributeStatus::STATE_REJECTED,
                    'item3' => ItemAttributeStatus::STATE_PENDING,
                ],
                Status::STATE_APPROVED_ON_ITEM
            ]
        ];
    }

    public function itemLabelDataProvider()
    {
        $state = 'Test_state';
        return [
            [Status::STATE_PENDING, 'Pending'],
            [Status::STATE_AUTHORIZED, 'Authorized'],
            [Status::STATE_PARTIAL_AUTHORIZED, 'Partially Authorized'],
            [Status::STATE_RECEIVED, 'Return Received'],
            [Status::STATE_RECEIVED_ON_ITEM, 'Return Partially Received'],
            [Status::STATE_APPROVED, 'Approved'],
            [Status::STATE_APPROVED_ON_ITEM, 'Partially Approved'],
            [Status::STATE_REJECTED, 'Rejected'],
            [Status::STATE_REJECTED_ON_ITEM, 'Partially Rejected'],
            [Status::STATE_DENIED, 'Denied'],
            [Status::STATE_CLOSED, 'Closed'],
            [Status::STATE_PROCESSED_CLOSED, 'Processed and Closed'],
            [$state, 'Test_state']
        ];
    }

    public function statusByItemsExceptionDataProvider()
    {
        return [
            ['test_not_array'],
            [
                []
            ],
            [
                ['item1' => 'WRONG_TEST_STATUS']
            ],
        ];
    }

    public function buttonDisabledStatusDataProvider()
    {
        return [
            [Status::STATE_PARTIAL_AUTHORIZED, true],
            [Status::STATE_RECEIVED, true],
            [Status::STATE_RECEIVED_ON_ITEM, true],
            [Status::STATE_APPROVED_ON_ITEM, true],
            [Status::STATE_REJECTED_ON_ITEM, true],
            [Status::STATE_CLOSED, true],
            [Status::STATE_PROCESSED_CLOSED, true],
            ['TEST_STATUS', false]
        ];
    }
}
