<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Block\Adminhtml\Customersegment\Grid;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Helper\Data;
use Magento\CustomerSegment\Block\Adminhtml\Customersegment\Grid\Chooser;
use Magento\CustomerSegment\Model\SegmentFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\System\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChooserTest extends TestCase
{
    /**
     * @var Chooser
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
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Context
     */
    protected $context;
    /**
     * @var MockObject
     */
    private $urlBuilder;

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

        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class, [], '', false);

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

        $this->model = new Chooser(
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

    public function testGetRowClickCallback()
    {
        $data = 'test_row_click_callback';

        $this->model->setData('row_click_callback', $data);

        $this->assertEquals($data, $this->model->getRowClickCallback());
    }

    public function testGetGridUrl()
    {
        $this->urlBuilder
            ->expects($this->any())
            ->method('getUrl')
            ->with('customersegment/index/chooserGrid', ['_current' => true])
            ->willReturn('http://some_url');

        $this->assertStringContainsString('http://some_url', (string)$this->model->getGridUrl());
    }
}
