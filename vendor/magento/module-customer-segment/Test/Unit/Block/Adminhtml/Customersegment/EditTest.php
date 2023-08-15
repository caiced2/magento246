<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Block\Adminhtml\Customersegment;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\CustomerSegment\Block\Adminhtml\Customersegment\Edit;
use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

class EditTest extends TestCase
{
    /**
     * @var Edit
     */
    protected $model;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\CustomerSegment\Model\Segment
     */
    protected $segment;

    /**
     * @var BackendUrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ButtonList
     */
    protected $buttonList;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Context
     */
    protected $context;

    protected function setUp(): void
    {
        $this->segment = $this->getMockBuilder(Segment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getSegmentId', 'getName'])
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry
            ->expects($this->any())
            ->method('registry')
            ->with('current_customer_segment')
            ->willReturn($this->segment);

        $this->urlBuilder = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->getMockForAbstractClass();

        $this->buttonList = $this->getMockBuilder(ButtonList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buttonList->expects($this->any())->method('update')->willReturnSelf();
        $this->buttonList->expects($this->any())->method('add')->willReturnSelf();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->request->expects($this->any())->method('getParam')->willReturn(1);
        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context
            ->expects($this->once())
            ->method('getButtonList')
            ->willReturn($this->buttonList);
        $this->context
            ->expects($this->once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilder);
        $this->context
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->context
            ->expects($this->once())
            ->method('getEscaper')
            ->willReturn($this->escaper);

        $this->model = new Edit(
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
            $this->buttonList,
            $this->request,
            $this->escaper,
            $this->context
        );
    }

    public function testGetMatchUrl()
    {
        $this->segment
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->urlBuilder
            ->expects($this->any())
            ->method('getUrl')
            ->with('*/*/match', ['id' => $this->segment->getId()])
            ->willReturn('http://some_url');

        $this->assertStringContainsString('http://some_url', (string)$this->model->getMatchUrl());
    }

    public function testGetHeaderText()
    {
        $this->segment
            ->expects($this->once())
            ->method('getSegmentId')
            ->willReturn(false);

        $this->assertEquals('New Segment', $this->model->getHeaderText());
    }

    public function testGetHeaderTextWithSegmentId()
    {
        $segmentName = 'test_segment_name';

        $this->segment
            ->expects($this->once())
            ->method('getSegmentId')
            ->willReturn(1);
        $this->segment
            ->expects($this->once())
            ->method('getName')
            ->willReturn($segmentName);

        $this->escaper
            ->expects($this->once())
            ->method('escapeHtml')
            ->willReturn($segmentName);

        $this->assertEquals(sprintf("Edit Segment '%s'", $segmentName), $this->model->getHeaderText());
    }
}
