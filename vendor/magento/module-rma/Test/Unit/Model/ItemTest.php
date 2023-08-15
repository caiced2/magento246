<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Model\Item;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\RmaFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    /**
     * @var Item
     */
    protected $model;

    /**
     * @var \Magento\Rma\Model\ResourceModel\Item|MockObject
     */
    protected $resourceMock;

    /**
     * @var RmaFactory|MockObject
     */
    protected $rmaFactoryMock;

    /**
     * @var Rma|MockObject
     */
    protected $rmaMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->resourceMock = $this->createMock(\Magento\Rma\Model\ResourceModel\Item::class);
        $this->rmaFactoryMock = $this->createPartialMock(RmaFactory::class, ['create']);
        $this->rmaMock = $this->createPartialMock(Rma::class, ['getOrderId', 'load']);

        $this->serializer = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $objectManager->getObject(
            Item::class,
            [
                'resource' => $this->resourceMock,
                'rmaFactory' => $this->rmaFactoryMock,
                'data' => [
                    'order_item_id' => 3,
                    'rma_entity_id' => 4,
                ],
                'serializer' => $this->serializer
            ]
        );
    }

    /**
     * Test getOptions
     * @covers \Magento\Rma\Model\Item::getOptions
     */
    public function testGetOptions()
    {
        $json_options = json_encode(
            [
                "options" => [1, "options"],
                "additional_options" => [2, "additional_options"],
                "attributes_info" => [3, "attributes_info"]
            ]
        );
        $this->model->setProductOptions($json_options);
        $result = [1, "options", 2, "additional_options", 3, "attributes_info"];

        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->assertEquals($result, $this->model->getOptions());
    }

    /**
     * Test getReturnableQty
     */
    public function testGetReturnableQty()
    {
        $this->rmaFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->rmaMock);
        $this->rmaMock->expects($this->once())
            ->method('load')
            ->with(4)->willReturnSelf();
        $this->rmaMock->expects($this->once())
            ->method('getOrderId')
            ->willReturn(3);
        $this->resourceMock->expects($this->once())
            ->method('getReturnableItems')
            ->with(3)
            ->willReturn([3 => 100.50, 4 => 50.00]);
        $this->assertEquals(100.50, $this->model->getReturnableQty());
    }

    /**
     * Test setStatus method.
     *
     * @return void
     */
    public function testSetStatus(): void
    {
        $this->model->setStatus(1);
        $this->assertEquals(1, $this->model->getStatus());
    }
}
