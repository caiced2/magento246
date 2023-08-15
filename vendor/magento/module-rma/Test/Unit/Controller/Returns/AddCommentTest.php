<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Returns;

use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Status\History;
use Magento\Rma\Test\Unit\Controller\ReturnsTest;
use PHPUnit\Framework\MockObject\MockObject;

class AddCommentTest extends ReturnsTest
{
    /**
     * @var string
     */
    protected $name = 'AddComment';

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirect;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactory;

    /**
     * @var Data|MockObject
     */
    protected $rmaHelper;

    /**
     * @var Rma|MockObject
     */
    protected $rma;

    /**
     * @var Session|MockObject
     */
    protected $session;

    /**
     * @var History|MockObject
     */
    protected $history;

    /**
     * @inheirtDoc
     */
    protected function initContext(): void
    {
        $entityId = 7;
        $customerId = 8;
        $comment = 'comment';

        parent::initContext();

        $this->resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->resultFactory->expects($this->atLeastOnce())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirect);

        $this->context->expects($this->once())
            ->method('getResultFactory')
            ->willReturn($this->resultFactory);

        $this->request->expects($this->any())
            ->method('getParam')
            ->with('entity_id')
            ->willReturn($entityId);
        $this->request->expects($this->any())
            ->method('getPost')
            ->with('comment')
            ->willReturn($comment);

        $this->resultRedirect
            ->expects($this->once())
            ->method('setPath')
            ->with('*/*/view')
            ->willReturn($this->resultRedirect);

        $this->rmaHelper = $this->createMock(Data::class);
        $this->rmaHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->rma = $this->createPartialMock(
            Rma::class,
            ['load', 'getCustomerId', 'getId']
        );
        $this->rma->expects($this->once())
            ->method('load')
            ->with($entityId)
            ->willReturnSelf();
        $this->rma->expects($this->any())
            ->method('getId')
            ->willReturn($entityId);
        $this->rma->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->session = $this->createMock(Session::class);
        $this->session->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->history = $this->createMock(History::class);
        $this->history->expects($this->once())
            ->method('sendCustomerCommentEmail');

        $this->history->expects($this->once())
            ->method('saveComment')
            ->with($comment, true, false);
        $this->history->expects($this->once())
            ->method('setRmaEntityId')
            ->with($entityId)
            ->willReturnSelf();
        $this->objectManager
            ->method('get')
            ->withConsecutive([Data::class], [Session::class])
            ->willReturnOnConsecutiveCalls($this->rmaHelper, $this->session);
        $this->objectManager
            ->method('create')
            ->withConsecutive([Rma::class], [History::class])
            ->willReturnOnConsecutiveCalls($this->rma, $this->history);
    }

    /**
     * @return void
     */
    public function testAddCommentAction(): void
    {
        $this->coreRegistry->expects($this->atLeastOnce())
            ->method('registry')
            ->with('current_rma')
            ->willReturn($this->rma);
        $this->assertSame($this->resultRedirect, $this->controller->execute());
    }
}
