<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Controller\Adminhtml\Report\Customer\Wishlist;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Block\Widget\Grid\ExportInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Layout;
use Magento\MultipleWishlist\Controller\Adminhtml\Report\Customer\Wishlist\ExportCsv;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportCsvTest extends TestCase
{
    /** @var FileFactory|MockObject */
    protected $fileFactory;

    /** @var Context|MockObject */
    protected $context;

    /** @var ResultFactory|MockObject */
    protected $resultFactory;

    /** @var Layout|MockObject $resultLayout */
    protected $resultLayout;

    /** @var \Magento\Framework\View\Layout|MockObject */
    protected $layout;

    /** @var ExportInterface|MockObject */
    protected $exportGridBlock;

    /** @var ExportCsv */
    protected $controller;

    /** @var ResponseInterface|MockObject */
    protected $response;

    protected function setUp(): void
    {
        $this->fileFactory = $this->createPartialMock(
            FileFactory::class,
            ['create']
        );
        $this->resultLayout = $this->createMock(Layout::class);
        $this->layout = $this->createMock(\Magento\Framework\View\Layout::class);
        $this->exportGridBlock = $this->getMockForAbstractClass(
            ExportInterface::class,
            [],
            '',
            false
        );
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->response = $this->getMockForAbstractClass(
            ResponseInterface::class,
            [],
            '',
            false
        );

        $objectHelper = new ObjectManager($this);
        $this->context = $objectHelper->getObject(
            Context::class,
            [
                'resultFactory' => $this->resultFactory
            ]
        );
        $this->controller = new ExportCsv($this->context, $this->fileFactory);
    }

    public function testExecute()
    {
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultLayout);
        $this->resultLayout->expects($this->once())
            ->method('getLayout')
            ->willReturn($this->layout);
        $this->layout->expects($this->once())
            ->method('getChildBlock')
            ->with('adminhtml.block.report.customer.wishlist.grid', 'grid.export')
            ->willReturn($this->exportGridBlock);
        $this->exportGridBlock->expects($this->once())
            ->method('getCsvFile')
            ->willReturn('csvFile');
        $this->fileFactory->expects($this->once())
            ->method('create')
            ->with('customer_wishlists.csv', 'csvFile', DirectoryList::VAR_DIR)
            ->willReturn($this->response);

        $this->controller->execute();
    }
}
