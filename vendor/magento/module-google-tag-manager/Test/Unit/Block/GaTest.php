<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Block;

use Magento\Cookie\Helper\Cookie;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Block\Ga;
use Magento\GoogleTagManager\Helper\Data;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GaTest extends TestCase
{
    /** @var Ga */
    protected $ga;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var MockObject */
    protected $collectionFactory;

    /** @var Data|MockObject */
    protected $googleTagManagerHelper;

    /** @var Cookie|MockObject */
    protected $cookieHelper;

    /** @var \Magento\Framework\Json\Helper\Data|MockObject */
    protected $jsonHelper;

    /** @var StoreManagerInterface|MockObject */
    protected $storeManager;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->googleTagManagerHelper = $this->createMock(Data::class);
        $this->cookieHelper = $this->createMock(Cookie::class);
        $this->jsonHelper = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->ga = $this->objectManagerHelper->getObject(
            Ga::class,
            [
                'salesOrderCollection' => $this->collectionFactory,
                'googleAnalyticsData' => $this->googleTagManagerHelper,
                'cookieHelper' => $this->cookieHelper,
                'jsonHelper' => $this->jsonHelper,
                'storeManager' => $this->storeManager
            ]
        );
    }

    public function testToHtml()
    {
        $this->googleTagManagerHelper->expects($this->atLeastOnce())->method('isGoogleAnalyticsAvailable')
            ->willReturn(true);
        $this->ga->toHtml();
    }

    public function testGetStoreCurrencyCode()
    {
        $store = $this->createMock(Store::class);
        $store->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->with(null)->willReturn($store);
        $this->assertEquals('USD', $this->ga->getStoreCurrencyCode());
    }

    public function testGetOrdersDataEmptyOrderIds()
    {
        $this->assertEmpty($this->ga->getOrdersData());
    }

    public function testGetOrdersData()
    {
        $result = $this->prepareOrderDataMocks();
        $this->jsonHelper->expects($this->once())->method('jsonEncode')->with($result)->willReturn('{encoded_string}');
        $this->assertEquals("dataLayer.push({encoded_string});\n", $this->ga->getOrdersData());
    }

    public function testGetOrdersDataArray()
    {
        $result = $this->prepareOrderDataMocks();
        $this->assertEquals([$result], $this->ga->getOrdersDataArray());
    }

    public function testIsUserNotAllowSaveCookie()
    {
        $this->cookieHelper->expects($this->atLeastOnce())->method('isUserNotAllowSaveCookie')->willReturn(true);
        $this->assertTrue($this->ga->isUserNotAllowSaveCookie());
    }

    /**
     * @return array
     */
    private function prepareOrderDataMocks(): array
    {
        $this->ga->setOrderIds([12, 13]);
        $item1 = $this->createMock(Item::class);
        $item1->expects($this->atLeastOnce())->method('getSku')->willReturn('SKU-123');
        $item1->expects($this->atLeastOnce())->method('getName')->willReturn('Product Name');
        $item1->expects($this->atLeastOnce())->method('getBasePrice')->willReturn(85);
        $item1->expects($this->atLeastOnce())->method('getQtyOrdered')->willReturn(1);

        $item2 = $this->createMock(Item::class);
        $item2->expects($this->atLeastOnce())->method('getSku')->willReturn('SKU-123');
        $item2->expects($this->atLeastOnce())->method('getName')->willReturn('Product Name');
        $item2->expects($this->atLeastOnce())->method('getBasePrice')->willReturn(85);
        $item2->expects($this->atLeastOnce())->method('getQtyOrdered')->willReturn(1);

        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('getIncrementId')->willReturn('10002323');
        $order->expects($this->once())->method('getBaseGrandTotal')->willReturn(120);
        $order->expects($this->once())->method('getBaseTaxAmount')->willReturn(15);
        $order->expects($this->once())->method('getBaseShippingAmount')->willReturn(20);
        $order->expects($this->once())->method('getCouponCode')->willReturn('ABC123123');
        $order->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$item1, $item2]);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('addFieldToFilter')->with('entity_id', ['in' => [12, 13]]);
        $collection->expects($this->once())->method('getIterator')->willReturn(
            new \ArrayIterator([$order])
        );

        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);

        $store = $this->createMock(Store::class);
        $store->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->with(null)->willReturn($store);

        $result = [
            'ecommerce' => [
                'purchase' => [
                    'actionField' => [
                        'id' => '10002323',
                        'revenue' => 120,
                        'tax' => 15,
                        'shipping' => 20,
                        'coupon' => 'ABC123123'
                    ],
                    'products' => [
                        0 => [
                            'id' => 'SKU-123',
                            'name' => 'Product Name',
                            'price' => 85,
                            'quantity' => 1
                        ],
                        1 => [
                            'id' => 'SKU-123',
                            'name' => 'Product Name',
                            'price' => 85,
                            'quantity' => 1
                        ],
                    ],
                ],
                'currencyCode' => 'USD'
            ],
            'event' => 'purchase'
        ];
        return $result;
    }
}
