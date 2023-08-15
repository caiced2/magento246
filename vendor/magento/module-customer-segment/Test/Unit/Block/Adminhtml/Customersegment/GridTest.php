<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Block\Adminhtml\Customersegment;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\CustomerSegment\Block\Adminhtml\Customersegment\Grid;
use Magento\CustomerSegment\Model\SegmentFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Store\Model\System\Store;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GridTest extends TestCase
{
    /**
     * @var Grid
     */
    protected $model;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var SegmentFactory
     */
    protected $segmentFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var BackendUrlInterface
     */
    protected $urlBuilder;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Context
     */
    protected $context;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->store = $this->createMock(Store::class);
        $this->segmentFactory = $this->createPartialMock(
            SegmentFactory::class,
            ['create']
        );
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);

        $writeInterface = $this->getMockForAbstractClass(WriteInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->filesystem
            ->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn($writeInterface);

        $this->urlBuilder = $this->getMockForAbstractClass(\Magento\Framework\UrlInterface::class, [], '', false);

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context
            ->expects($this->once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilder);
        $this->context
            ->expects($this->once())
            ->method('getFilesystem')
            ->willReturn($this->filesystem);
        $this->context
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->model = new Grid(
            $this->context,
            $this->helper,
            $this->store,
            $this->segmentFactory
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->model,
            $this->helper,
            $this->store,
            $this->segmentFactory,
            $this->request,
            $this->filesystem,
            $this->urlBuilder,
            $this->context
        );
    }

    public function testGetRowUrl()
    {
        $this->model->setSegmentId(1);

        $object = new DataObject(['segment_id' => 1]);

        $this->urlBuilder
            ->expects($this->any())
            ->method('getUrl')
            ->with('*/*/edit', ['id' => $this->model->getSegmentId()])
            ->willReturn('http://some_url');

        $this->assertStringContainsString('http://some_url', (string)$this->model->getRowUrl($object));
    }

    public function testGetRowUrlNull()
    {
        $this->model->setIsChooserMode(true);

        $object = new DataObject(['segment_id' => 1]);

        $this->assertNull($this->model->getRowUrl($object));
    }

    public function testGetGridUrl()
    {
        $this->urlBuilder
            ->expects($this->any())
            ->method('getUrl')
            ->with('customersegment/index/grid', ['_current' => true])
            ->willReturn('http://some_url');

        $this->assertStringContainsString('http://some_url', (string)$this->model->getGridUrl());
    }

    public function testGetRowClickCallback()
    {
        $this->assertEquals('openGridRow', $this->model->getRowClickCallback());
    }

    public function testGetRowClickCallbackChooserMode()
    {
        $this->model->setIsChooserMode(true);

        $this->request
            ->expects($this->once())
            ->method('getParam')
            ->with('value_element_id')
            ->willReturn(1);

        $this->assertStringContainsString('function (grid, event) {', (string)$this->model->getRowClickCallback());
    }
}
