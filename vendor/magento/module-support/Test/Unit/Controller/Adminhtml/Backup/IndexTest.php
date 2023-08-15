<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Controller\Adminhtml\Backup;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Support\Controller\Adminhtml\Backup\Index;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Index
     */
    protected $indexAction;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            ['resultFactory' => $this->resultFactoryMock]
        );
        $this->indexAction = $this->objectManagerHelper->getObject(
            Index::class,
            ['context' => $this->context]
        );
    }

    /**
     * @return void
     */
    public function testExecuteReturnsPage(): void
    {
        /** @var Title|MockObject $title */
        $title = $this->createMock(Title::class);
        $title->expects($this->once())
            ->method('prepend')
            ->with(__('Data Collector'));

        /** @var Config|MockObject $pageConfig */
        $pageConfig = $this->createMock(Config::class);
        $pageConfig->expects($this->once())
            ->method('getTitle')
            ->willReturn($title);

        /** @var Page|MockObject $resultPage */
        $resultPage = $this->createMock(Page::class);
        $resultPage->expects($this->once())
            ->method('getConfig')
            ->willReturn($pageConfig);
        $resultPage->expects($this->once())
            ->method('setActiveMenu')
            ->with('Magento_Support::support_backup')
            ->willReturnSelf();
        $resultPage
            ->method('addBreadcrumb')
            ->withConsecutive([__('Support'), __('Support')], [__('Data Collector'), __('Data Collector')])
            ->willReturnOnConsecutiveCalls($resultPage, $resultPage);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($resultPage);

        $this->assertSame($resultPage, $this->indexAction->execute());
    }
}
