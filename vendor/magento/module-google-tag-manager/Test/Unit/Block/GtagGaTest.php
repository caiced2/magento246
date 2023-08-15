<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Block;

use Magento\Cookie\Helper\Cookie;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\GoogleTagManager\Block\GtagGa;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig as TagManagerConfig;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GtagGaTest extends TestCase
{
    /** @var GtagGa */
    protected $ga;

    /**
     * @var MockObject
     */
    private $cookieHelperMock;

    /**
     * @var MockObject
     */
    private $storeManagerMock;

    /**
     * @var TagManagerConfig|MockObject
     */
    private $tagManagerConfig;

    /**
     * @var SearchCriteriaBuilder|mixed|MockObject
     */
    private $searchCriteriaBuilder;
    /**
     * @var OrderRepositoryInterface|mixed|MockObject
     */
    private $orderRepository;
    /**
     * @var SerializerInterface|mixed|MockObject
     */
    private $serializerMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->once())
            ->method('getEscaper')
            ->willReturn($objectManager->getObject(Escaper::class));

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $contextMock->expects($this->once())->method('getStoreManager')->willReturn($this->storeManagerMock);

        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->onlyMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->onlyMethods(['addFilter', 'create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->tagManagerConfig = $this->getMockBuilder(TagManagerConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieHelperMock = $this->getMockBuilder(Cookie::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ga = $objectManager->getObject(
            GtagGa::class,
            [
                'context' => $contextMock,
                'googleGtagConfig' => $this->tagManagerConfig,
                'cookieHelper' => $this->cookieHelperMock,
                'serializer' => $this->serializerMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'orderRepository' => $this->orderRepository
            ]
        );
    }

    public function testGetStoreCurrencyCode()
    {
        $store = $this->createMock(Store::class);
        $store->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManagerMock->expects($this->atLeastOnce())->method('getStore')->with(null)->willReturn($store);
        $this->assertEquals('USD', $this->ga->getStoreCurrencyCode());
    }

    public function testGetOrdersDataEmptyOrderIds()
    {
        $this->assertEmpty($this->ga->getOrdersData());
    }

    public function testGetOrdersDataArray()
    {
        $result = $this->prepareOrderDataMocks();
        $this->assertEquals([$result], $this->ga->getOrdersDataArray());
    }

    public function testIsUserNotAllowSaveCookie()
    {
        $this->cookieHelperMock->expects($this->atLeastOnce())->method('isUserNotAllowSaveCookie')->willReturn(true);
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

        $searchCriteria = $this
            ->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderSearchResult = $this->getMockBuilder(OrderSearchResultInterface::class)
            ->onlyMethods(['getTotalCount', 'getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderRepository->method('getList')->willReturn($orderSearchResult);
        $orderSearchResult->method('getTotalCount')->willReturn(1);
        $orderSearchResult->method('getItems')->willReturn([ 1 => $order]);
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $store = $this->createMock(Store::class);
        $store->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManagerMock->expects($this->atLeastOnce())->method('getStore')->with(null)->willReturn($store);

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
