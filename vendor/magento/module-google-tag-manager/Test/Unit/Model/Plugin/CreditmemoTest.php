<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Model\Plugin;

use Magento\Backend\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Helper\Data;
use Magento\GoogleTagManager\Model\Plugin\Creditmemo;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo\Item;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditmemoTest extends TestCase
{
    /** @var Creditmemo */
    protected $creditmemo;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Data|MockObject */
    protected $helper;

    /** @var Session|MockObject */
    protected $session;

    /**
     * @var CreditmemoRepositoryInterface|MockObject
     */
    private $creditmemoRepositoryMock;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->session = $this->getMockBuilder(Session::class)
            ->addMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->creditmemoRepositoryMock = $this->getMockForAbstractClass(
            CreditmemoRepositoryInterface::class
        );
        $this->creditmemo = $this->objectManagerHelper->getObject(
            Creditmemo::class,
            [
                'helper' => $this->helper,
                'backendSession' => $this->session
            ]
        );
    }

    public function testAfterSave()
    {
        $this->helper->expects($this->atLeastOnce())->method('isTagManagerAvailable')->willReturn(false);

        $this->session->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                [
                    'googleanalytics_creditmemo_order',
                    '00000001'
                ],
                [
                    'googleanalytics_creditmemo_store_id',
                    2
                ],
                [
                    'googleanalytics_creditmemo_revenue',
                    '19.99'
                ],
                [
                    'googleanalytics_creditmemo_products',
                    [
                        [
                            'id' => 'Item 1',
                            'quantity' => 3
                        ]
                    ]
                ]
            )
            ->willReturnSelf();

        $order = $this->createMock(Order::class);
        $order->expects($this->any())->method('getIncrementId')->willReturn('00000001');
        $order->expects($this->any())->method('getBaseGrandTotal')->willReturn('29.99');

        $item1 = $this->createMock(Item::class);
        $item1->expects($this->any())->method('getQty')->willReturn(3);
        $item1->expects($this->any())->method('getSku')->willReturn('Item 1');

        $item2 = $this->createMock(Item::class);
        $item2->expects($this->any())->method('getQty')->willReturn(0);
        $item2->expects($this->any())->method('getSku')->willReturn('Item 2');

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$item1, $item2]));

        /** @var Order\Creditmemo|MockObject $result */
        $result = $this->createMock(Order\Creditmemo::class);
        $result->expects($this->any())->method('getOrder')->willReturn($order);
        $result->expects($this->any())->method('getStoreId')->willReturn(2);
        $result->expects($this->any())->method('getBaseGrandTotal')->willReturn('19.99');
        $result->expects($this->any())->method('getItemsCollection')->willReturn($collection);
        $this->assertSame($result, $this->creditmemo->afterSave($this->creditmemoRepositoryMock, $result));
    }

    public function testAfterSaveNotAvailable()
    {
        $this->helper->expects($this->atLeastOnce())->method('isTagManagerAvailable')->willReturn(false);
        /** @var Order\Creditmemo|MockObject $result */
        $result = $this->createMock(Order\Creditmemo::class);
        $result->expects($this->never())->method('getOrder');
        $this->session->expects($this->never())->method('setData');

        $this->assertSame($result, $this->creditmemo->afterSave($this->creditmemoRepositoryMock, $result));
    }
}
