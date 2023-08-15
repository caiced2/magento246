<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Test\Unit\Model\Observer;

use Magento\AsyncOrder\Model\Order as AsyncOrder;
use Magento\Sales\Model\Order;
use Magento\Framework\Event;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\Event\Observer;
use Magento\AsyncOrder\Observer\ProcessGuestOrderObserver;
use Magento\AsyncOrder\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessGuestOrderObserverTest extends TestCase
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var AsyncOrder
     */
    private $origOrder;

    /**
     * @var ProcessGuestOrderObserver
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->origOrder = $this->getMockBuilder(AsyncOrder::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getCustomerId', 'getStatus'])
            ->getMock();
        $this->orderFactory->expects($this->any())->method('create')->willReturn($this->origOrder);

        $this->model = $objectManager->getObject(
            ProcessGuestOrderObserver::class,
            [
                'deploymentConfig' => $this->deploymentConfig,
                'orderFactory' => $this->orderFactory
            ]
        );
    }

    /**
     * @param int|null $customerId
     * @param int|null $origOrderStatus
     * @param int|null $origCustomerId
     * @dataProvider processDataProvider
     */
    public function testExecute(?int $customerId, ?string $origOrderStatus, ?int $origCustomerId): void
    {
        $entityId = 123;

        $observer = $this->createMock(Observer::class);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId', 'setCustomerId', 'getEntityId'])
            ->getMock();
        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder'])
            ->getMock();

        $this->deploymentConfig->expects($this->once())->method('get')->with(
            OrderManagement::ASYNC_ORDER_OPTION_PATH
        )->willReturn(true);

        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getOrder')->willReturn($order);
        $order->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        if ($customerId === null) {
            $order->expects($this->once())->method('getEntityId')->willReturn($entityId);
            $this->origOrder->expects($this->once())->method('load')->with($entityId)->willReturnSelf();
            $this->origOrder->expects($this->once())->method('getCustomerId')->willReturn($origCustomerId);
            $this->origOrder->expects($this->once())->method('getStatus')->willReturn($origOrderStatus);
            if ($origCustomerId !== null && $origOrderStatus === OrderManagement::STATUS_RECEIVED) {
                $order->expects($this->once())->method('setCustomerId')->with($origCustomerId)->willReturnSelf();
            } else {
                $order->expects($this->never())->method('setCustomerId');
            }
        } else {
            $order->expects($this->never())->method('getEntityId');
            $order->expects($this->never())->method('setCustomerId');
            $this->origOrder->expects($this->never())->method('load');
            $this->origOrder->expects($this->never())->method('getCustomerId');
            $this->origOrder->expects($this->never())->method('getStatus');
        }

        $this->assertEquals($this->model, $this->model->execute($observer));
    }

    public function processDataProvider(): array
    {
        return [
            [
                'customerId' => null,
                'origOrderStatus' => OrderManagement::STATUS_RECEIVED,
                'origCustomerId' => 333
            ],
            [
                'customerId' => null,
                'origOrderStatus' => OrderManagement::STATUS_RECEIVED,
                'origCustomerId' => null
            ],
            [
                'customerId' => null,
                'origOrderStatus' => 'some status',
                'origCustomerId' => 333
            ],
            [
                'customerId' => 444,
                'origOrderStatus' => OrderManagement::STATUS_RECEIVED,
                'origCustomerId' => 333
            ],
            [
                'customerId' => 444,
                'origOrderStatus' => 'some status',
                'origCustomerId' => null
            ],
            [
                'customerId' => 444,
                'origOrderStatus' => OrderManagement::STATUS_RECEIVED,
                'origCustomerId' => null
            ]
        ];
    }
}
