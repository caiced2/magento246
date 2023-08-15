<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Block\Adminhtml\Creditmemo;

use Magento\Backend\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Block\Adminhtml\Creditmemo\Ga;
use Magento\GoogleTagManager\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GaTest extends TestCase
{
    /** @var Ga */
    protected $ga;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Session|MockObject */
    protected $session;

    /** @var Data|MockObject */
    protected $googleTagManagerHelper;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->googleTagManagerHelper = $this->createMock(Data::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->ga = $this->objectManagerHelper->getObject(
            Ga::class,
            [
                'googleAnalyticsData' => $this->googleTagManagerHelper,
                'backendSession' => $this->session
            ]
        );
    }

    /**
     * @param int|null $orderId
     * @param int|string $expected
     *
     * @dataProvider getOrderIdDataProvider
     */
    public function testGetOrderId($orderId, $expected)
    {
        $this->googleTagManagerHelper->expects($this->any())->method('isGoogleAnalyticsAvailable')
            ->willReturn(true);
        $this->session->expects($this->any())->method('getData')->with('googleanalytics_creditmemo_order', true)
            ->willReturn($orderId);
        $this->assertEquals($expected, $this->ga->getOrderId());
    }

    public function getOrderIdDataProvider()
    {
        return [
            [10, 10],
            [null, '']
        ];
    }

    /**
     * @param int|null $revenue
     * @param int|string $expected
     *
     * @dataProvider getRevenueDataProvider
     */
    public function testGetRevenue($revenue, $expected)
    {
        $this->session->expects($this->any())->method('getData')->with('googleanalytics_creditmemo_revenue', true)
            ->willReturn($revenue);
        $this->assertEquals($expected, $this->ga->getRevenue());
    }

    public function getRevenueDataProvider()
    {
        return [
            [101, 101],
            [null, '']
        ];
    }

    /**
     * @param int|null $products
     * @param int|string $expected
     *
     * @dataProvider getProductsDataProvider
     */
    public function testGetProducts($products, $expected)
    {
        $this->session->expects($this->any())->method('getData')->with('googleanalytics_creditmemo_products', true)
            ->willReturn($products);
        $this->assertEquals($expected, $this->ga->getProducts());
    }

    public function getProductsDataProvider()
    {
        return [
            [[1,2,3], [1,2,3]],
            [null, []]
        ];
    }

    public function testGetRefundJson()
    {
        $this->googleTagManagerHelper->expects($this->any())->method('isGoogleAnalyticsAvailable')
            ->willReturn(true);
        $this->session->expects($this->any())->method('getData')->willReturnMap([
            ['googleanalytics_creditmemo_order', true, 11],
            ['googleanalytics_creditmemo_revenue', true, 22],
            ['googleanalytics_creditmemo_products', true, [31, 32]],
        ]);
        $this->assertEquals(
            '{"event":"refund","ecommerce":{"refund":{"actionField":{"id":11,"revenue":22},"products":[31,32]}}}',
            $this->ga->getRefundJson()
        );
    }
}
