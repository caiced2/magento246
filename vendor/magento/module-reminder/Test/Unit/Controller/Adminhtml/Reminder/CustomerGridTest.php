<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Controller\Adminhtml\Reminder;

use Magento\Reminder\Block\Adminhtml\Reminder\Edit\Tab\Customers;
use Magento\Reminder\Controller\Adminhtml\Reminder\CustomerGrid;
use Magento\Reminder\Test\Unit\Controller\Adminhtml\AbstractReminder;

class CustomerGridTest extends AbstractReminder
{
    /**
     * @return void
     */
    public function testExecute(): void
    {
        $this->initRule();
        $this->view->expects($this->any())->method('getLayout')->willReturn($this->layout);
        $this->layout->expects($this->any())->method('createBlock')
            ->with(Customers::class)->willReturn($this->block);
        $this->response->expects($this->once())->method('setBody')->willReturn(true);
        $this->block->expects($this->once())->method('toHtml')->willReturn(true);

        $customerGridController = new CustomerGrid(
            $this->context,
            $this->coreRegistry,
            $this->ruleFactory,
            $this->conditionFactory,
            $this->dataFilter,
            $this->timeZoneResolver
        );
        $customerGridController->execute();
    }
}
