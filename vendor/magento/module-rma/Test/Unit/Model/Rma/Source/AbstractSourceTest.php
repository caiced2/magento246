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
use Magento\Rma\Model\Item\Attribute\Source\StatusFactory;
use Magento\Rma\Model\Rma\Source\Status;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractSourceTest extends TestCase
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
            Status::class,
            [
                'attrOptionCollectionFactory' => $this->attrOptionCollectionFactoryMock,
                'attrOptionFactory' => $this->attrOptionFactoryMock,
                'statusFactory' => $this->statusFactoryMock,
            ]
        );
    }

    /**
     * @dataProvider getAllOptionsDataProvider
     * @param bool $withLabels
     * @param array $expected
     */
    public function testGetAllOptions($withLabels, $expected)
    {
        $this->assertEquals($expected, $this->status->getAllOptions($withLabels));
    }

    public function testGetAllOptionsForGrid()
    {
        $expected = [
            Status::STATE_PENDING => 'Pending',
            Status::STATE_AUTHORIZED => 'Authorized',
            Status::STATE_PARTIAL_AUTHORIZED => 'Partially Authorized',
            Status::STATE_RECEIVED => 'Return Received',
            Status::STATE_RECEIVED_ON_ITEM => 'Return Partially Received' ,
            Status::STATE_APPROVED_ON_ITEM => 'Partially Approved',
            Status::STATE_REJECTED_ON_ITEM => 'Partially Rejected',
            Status::STATE_CLOSED => 'Closed',
            Status::STATE_PROCESSED_CLOSED => 'Processed and Closed',
        ];
        $this->assertEquals($expected, $this->status->getAllOptionsForGrid());
    }

    public function getAllOptionsDataProvider()
    {
        return [
            [
                true,
                [
                    ['label' => 'Pending', 'value' => Status::STATE_PENDING],
                    ['label' => 'Authorized', 'value' => Status::STATE_AUTHORIZED],
                    ['label' => 'Partially Authorized', 'value' => Status::STATE_PARTIAL_AUTHORIZED],
                    ['label' => 'Return Received', 'value' => Status::STATE_RECEIVED],
                    ['label' => 'Return Partially Received', 'value' => Status::STATE_RECEIVED_ON_ITEM],
                    ['label' => 'Partially Approved', 'value' => Status::STATE_APPROVED_ON_ITEM],
                    ['label' => 'Partially Rejected', 'value' => Status::STATE_REJECTED_ON_ITEM],
                    ['label' => 'Closed', 'value' => Status::STATE_CLOSED],
                    ['label' => 'Processed and Closed', 'value' => Status::STATE_PROCESSED_CLOSED],
                ],
            ],
            [
                false,
                [
                    Status::STATE_PENDING,
                    Status::STATE_AUTHORIZED,
                    Status::STATE_PARTIAL_AUTHORIZED,
                    Status::STATE_RECEIVED,
                    Status::STATE_RECEIVED_ON_ITEM,
                    Status::STATE_APPROVED_ON_ITEM,
                    Status::STATE_REJECTED_ON_ITEM,
                    Status::STATE_CLOSED,
                    Status::STATE_PROCESSED_CLOSED
                ]
            ]
        ];
    }
}
