<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Guest;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Status\History;
use Magento\Rma\Test\Unit\Controller\GuestTest;
use Magento\Sales\Model\Order;

class AddCommentTest extends GuestTest
{
    /**
     * @var string
     */
    protected $name = 'AddComment';

    /**
     * @return void
     */
    public function testAddCommentAction(): void
    {
        $entityId = 7;
        $orderId = 5;
        $comment = 'comment';

        $this->request->expects($this->any())
            ->method('getParam')
            ->with('entity_id')
            ->willReturn($entityId);
        $this->request->expects($this->any())
            ->method('getPost')
            ->with('comment')
            ->willReturn($comment);

        $this->rmaHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->salesGuestHelper->expects($this->once())
            ->method('loadValidOrder')
            ->with($this->request)
            ->willReturn(true);

        $rma = $this->createPartialMock(
            Rma::class,
            ['load', 'getCustomerId', 'getId', 'getOrderId']
        );
        $rma->expects($this->once())
            ->method('load')
            ->with($entityId)->willReturnSelf();
        $rma->expects($this->any())
            ->method('getId')
            ->willReturn($entityId);
        $rma->expects($this->any())
            ->method('getOrderId')
            ->willReturn($orderId);

        $order = $this->createPartialMock(
            Order::class,
            ['getCustomerId', 'load', 'getId']
        );
        $order->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);

        $history = $this->createMock(History::class);
        $history->expects($this->once())
            ->method('sendCustomerCommentEmail');
        $history->expects($this->once())
            ->method('saveComment')
            ->with($comment, true, false);

        $this->objectManager
            ->method('create')
            ->withConsecutive([Rma::class], [History::class])
            ->willReturnOnConsecutiveCalls($rma, $history);

        $this->coreRegistry
            ->method('registry')
            ->withConsecutive(['current_order'], ['current_rma'])
            ->willReturnOnConsecutiveCalls($order, $rma);

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/view', ['entity_id' => $entityId])
            ->willReturnSelf();

        $this->assertInstanceOf(Redirect::class, $this->controller->execute());
    }
}
