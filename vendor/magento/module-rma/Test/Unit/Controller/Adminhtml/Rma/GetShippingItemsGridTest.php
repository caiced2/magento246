<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

use Magento\Framework\View\Layout;
use Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping\Grid;
use Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest;

class GetShippingItemsGridTest extends RmaTest
{
    protected $name = 'GetShippingItemsGrid';

    public function testAction()
    {
        $response = 'testResponse';

        $block = $this->createMock(Grid::class);
        $block->expects($this->once())
            ->method('toHtml')
            ->willReturn($response);

        $layoutMock = $this->getMockBuilder(Layout::class)
            ->addMethods(['renderLayout'])
            ->onlyMethods(['getBlock'])
            ->disableOriginalConstructor()
            ->getMock();
        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('magento_rma_getshippingitemsgrid')
            ->willReturn($block);

        $this->viewMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with($response);

        $this->action->execute();
    }
}
