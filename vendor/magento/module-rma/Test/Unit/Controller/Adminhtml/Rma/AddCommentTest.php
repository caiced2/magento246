<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

use Magento\Framework\Json\Helper\Data;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest;

class AddCommentTest extends RmaTest
{
    protected $name = 'AddComment';

    public function testAddCommentsAction()
    {
        $commentText = 'some comment';
        $visibleOnFront = true;
        $blockContents = [
            $commentText,
        ];
        $layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $blockMock = $this->getMockForAbstractClass(BlockInterface::class);
        $jsonHelperMock = $this->createMock(Data::class);

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn(
                [
                    'comment' => $commentText,
                    'is_visible_on_front' => $visibleOnFront,
                    'is_customer_notified' => true,
                ]
            );
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('current_rma')
            ->willReturn($this->rmaModelMock);
        $this->rmaModelMock->expects($this->once())
            ->method('getId')
            ->willReturn(10);
        $this->statusHistoryMock->expects($this->once())
            ->method('setRmaEntityId')
            ->with(10)
            ->willReturnSelf();
        $this->statusHistoryMock->expects($this->once())
            ->method('setComment')
            ->with($commentText);
        $this->viewMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);
        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('comments_history')
            ->willReturn($blockMock);
        $blockMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($blockContents);
        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(Data::class)
            ->willReturn($jsonHelperMock);
        $jsonHelperMock->expects($this->once())
            ->method('jsonEncode')
            ->willReturn($commentText);

        $this->responseMock->expects($this->once())
            ->method('representJson')
            ->with($commentText);

        $this->assertNull($this->action->execute());
    }
}
