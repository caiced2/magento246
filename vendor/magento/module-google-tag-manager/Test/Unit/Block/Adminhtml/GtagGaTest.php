<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Block\Adminhtml;

use Magento\Backend\Model\Session;
use Magento\Cookie\Helper\Cookie;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\GoogleTagManager\Block\Adminhtml\GtagGa;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\ManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GtagGaTest extends TestCase
{
    /** @var GtagGa */
    protected $ga;

    /** @var ObjectManagerHelper */
    protected $objectManager;

    /** @var \Magento\Framework\Json\Helper\Data|MockObject */
    protected $data;

    /** @var Session|MockObject */
    protected $session;

    /** @var StoreManagerInterface|MockObject */
    protected $storeManagerMock;

    /** @var ManagerInterface|MockObject */
    protected $eventManager;

    /**
     * @var TagManagerConfig|MockObject
     */
    private $tagManagerConfig;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManagerHelper($this);

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->once())
            ->method('getEscaper')
            ->willReturn($this->objectManager->getObject(Escaper::class));

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $contextMock->expects($this->once())->method('getStoreManager')->willReturn($this->storeManagerMock);

        $orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->onlyMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->onlyMethods(['addFilter', 'create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->tagManagerConfig = $this->getMockBuilder(TagManagerConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cookieHelperMock = $this->getMockBuilder(Cookie::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->createMock(Session::class);

        $this->ga = $this->objectManager->getObject(
            GtagGa::class,
            [
                'context' => $contextMock,
                'googleGtagConfig' => $this->tagManagerConfig,
                'cookieHelper' => $cookieHelperMock,
                'serializer' => $serializerMock,
                'searchCriteriaBuilder' => $searchCriteriaBuilder,
                'orderRepository' => $orderRepository,
                'backendSession' => $this->session
            ]
        );
    }

    public function testGetOrderId()
    {
        $this->session->expects($this->any())->method('getData')->with('googleanalytics_creditmemo_order', false)
            ->willReturn(10);
        $this->assertEquals(10, $this->ga->getOrderId());
    }

    public function testGetStoreCurrencyCode()
    {
        $this->session->expects($this->any())->method('getData')->with('googleanalytics_creditmemo_store_id', false)
            ->willReturn(3);
        $store = $this->createMock(Store::class);
        $store->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManagerMock->expects($this->atLeastOnce())->method('getStore')->with(3)->willReturn($store);
        $this->assertEquals('USD', $this->ga->getStoreCurrencyCode());
    }
}
