<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

use Magento\Backend\Block\Menu;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Result\Page;
use Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest;

class IndexTest extends RmaTest
{
    protected $name = 'Index';

    public function testIndexAction()
    {
        $layoutInterfaceMock = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $resultPageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $blockMock = $this->getMockBuilder(Menu::class)
            ->addMethods(['setActive'])
            ->onlyMethods(['getMenuModel'])
            ->disableOriginalConstructor()
            ->getMock();
        $menuModelMock = $this->createMock(\Magento\Backend\Model\Menu::class);
        $this->viewMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutInterfaceMock);
        $this->viewMock->expects($this->once())->method('getPage')->willReturn($resultPageMock);
        $resultPageMock->expects($this->once())->method('getConfig')->willReturn($pageConfigMock);
        $pageConfigMock->expects($this->any())->method('getTitle')->willReturn($this->titleMock);
        $layoutInterfaceMock->expects($this->once())
            ->method('getBlock')
            ->with('menu')
            ->willReturn($blockMock);
        $blockMock->expects($this->once())
            ->method('setActive')
            ->with('Magento_Rma::sales_magento_rma_rma');
        $blockMock->expects($this->once())
            ->method('getMenuModel')
            ->willReturn($menuModelMock);
        $menuModelMock->expects($this->once())
            ->method('getParentItems')
            ->willReturn([]);
        $this->titleMock->expects($this->once())
            ->method('prepend')
            ->with(__('Returns'));
        $this->viewMock->expects($this->once())
            ->method('renderLayout');

        $this->assertNull($this->action->execute());
    }
}
