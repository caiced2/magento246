<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Block\Adminhtml\Report\Customer\Segment;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\CustomerSegment\Block\Adminhtml\Report\Customer\Segment\Detail;
use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class DetailTest extends TestCase
{
    /**
     * @var Detail
     */
    protected $model;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Segment
     */
    protected $segment;

    /**
     * @var BackendUrlInterface
     */
    protected $urlBuilder;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var ButtonList
     */
    protected $buttonList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Context
     */
    protected $context;

    protected function setUp(): void
    {
        $this->segment = $this->createMock(Segment::class);

        $this->registry = $this->createMock(Registry::class);
        $this->registry
            ->expects($this->any())
            ->method('registry')
            ->with('current_customer_segment')
            ->willReturn($this->segment);

        $this->urlBuilder = $this->getMockForAbstractClass(\Magento\Framework\UrlInterface::class, [], '', false);
        $this->layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $this->buttonList = $this->createMock(ButtonList::class);
        $this->buttonList
            ->expects($this->any())
            ->method('add')->willReturnSelf();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context
            ->expects($this->once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilder);
        $this->context
            ->expects($this->once())
            ->method('getLayout')
            ->willReturn($this->layout);
        $this->context
            ->expects($this->once())
            ->method('getButtonList')
            ->willReturn($this->buttonList);
        $this->context
            ->expects($this->once())
            ->method('getStoreManager')
            ->willReturn($this->storeManager);

        $this->model = new Detail(
            $this->context,
            $this->registry
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->model,
            $this->segment,
            $this->registry,
            $this->urlBuilder,
            $this->layout,
            $this->storeManager,
            $this->buttonList,
            $this->context
        );
    }

    public function testGetRefreshUrl()
    {
        $this->urlBuilder
            ->expects($this->once())
            ->method('getUrl')
            ->with('customersegment/*/refresh', ['_current' => true])
            ->willReturn('http://some_url');

        $this->assertStringContainsString('http://some_url', (string)$this->model->getRefreshUrl());
    }

    public function testGetBackUrl()
    {
        $this->urlBuilder
            ->expects($this->once())
            ->method('getUrl')
            ->with('customersegment/*/segment')
            ->willReturn('http://some_url');

        $this->assertStringContainsString('http://some_url', (string)$this->model->getBackUrl());
    }

    public function testGetCustomerSegment()
    {
        $result = $this->model->getCustomerSegment();

        $this->assertInstanceOf(Segment::class, $result);
        $this->assertEquals($this->segment, $result);
    }

    public function testGetWebsites()
    {
        $data = [
            1 => 'website_1',
            2 => 'website_2',
        ];

        $this->storeManager
            ->expects($this->once())
            ->method('getWebsites')
            ->willReturn($data);

        $result = $this->model->getWebsites();

        $this->assertIsArray($result);
        $this->assertEquals($data, $result);
    }
}
