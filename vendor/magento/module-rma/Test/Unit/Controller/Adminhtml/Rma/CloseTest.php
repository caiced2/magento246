<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

use Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest;

class CloseTest extends RmaTest
{
    protected $name = 'Close';

    public function testCloseAction()
    {
        $entityId = 1;
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['entity_id', null, $entityId],
                ]
            );
        $this->rmaModelMock->expects($this->once())
            ->method('load')
            ->with($entityId)->willReturnSelf();
        $this->rmaModelMock->expects($this->once())
            ->method('canClose')
            ->willReturn(true);
        $this->rmaModelMock->expects($this->once())
            ->method('close')->willReturnSelf();
        $this->statusHistoryMock->expects($this->once())
            ->method('saveSystemComment');

        $this->assertNull($this->action->execute());
    }
}
