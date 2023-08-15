<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Block\Adminhtml;

use Magento\Backend\Model\Session;
use Magento\Cookie\Helper\Cookie;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Block\Adminhtml\Ga;
use Magento\GoogleTagManager\Helper\Data;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GaTest extends TestCase
{
    /** @var Ga */
    protected $ga;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Data|MockObject */
    protected $googleTagManagerHelper;

    /** @var Cookie|MockObject */
    protected $cookieCookieHelper;

    /** @var \Magento\Framework\Json\Helper\Data|MockObject */
    protected $data;

    /** @var Session|MockObject */
    protected $session;

    /** @var StoreManagerInterface|MockObject */
    protected $storeManager;

    protected function setUp(): void
    {
        $this->googleTagManagerHelper = $this->createMock(Data::class);
        $this->cookieCookieHelper = $this->createMock(Cookie::class);
        $this->data = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->session = $this->createMock(Session::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->ga = $this->objectManagerHelper->getObject(
            Ga::class,
            [
                'googleAnalyticsData' => $this->googleTagManagerHelper,
                'cookieHelper' => $this->cookieCookieHelper,
                'jsonHelper' => $this->data,
                'backendSession' => $this->session,
                'storeManager' => $this->storeManager
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
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->with(3)->willReturn($store);
        $this->assertEquals('USD', $this->ga->getStoreCurrencyCode());
    }

    public function testToHtml()
    {
        $this->googleTagManagerHelper->expects($this->atLeastOnce())->method('isGoogleAnalyticsAvailable')
            ->willReturn(true);
        $this->session->expects($this->atLeastOnce())
            ->method('getData')
            ->with('googleanalytics_creditmemo_order', false)
            ->willReturn(10);
        $this->ga->toHtml();
    }

    public function testToHtmlEmptyOrderId()
    {
        $this->googleTagManagerHelper->expects($this->never())->method('isGoogleAnalyticsAvailable');
        $this->session->expects($this->atLeastOnce())
            ->method('getData')
            ->with('googleanalytics_creditmemo_order', false)
            ->willReturn(null);
        $this->ga->toHtml();
    }
}
