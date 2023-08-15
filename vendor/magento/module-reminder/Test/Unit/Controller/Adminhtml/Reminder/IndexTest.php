<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\Reminder\Test\Unit\Controller\Adminhtml\Reminder;

use Magento\Reminder\Controller\Adminhtml\Reminder\Index;
use Magento\Reminder\Test\Unit\Controller\Adminhtml\AbstractReminder;

class IndexTest extends AbstractReminder
{
    /**
     * @var Index
     */
    protected $indexController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indexController = new Index(
            $this->context,
            $this->coreRegistry,
            $this->ruleFactory,
            $this->conditionFactory,
            $this->dataFilter,
            $this->timeZoneResolver
        );
    }

    public function testExecute()
    {
        $this->view->expects($this->any())->method('getLayout')->willReturn($this->layout);
        $this->layout->expects($this->any())->method('getBlock')->with('menu')->willReturn($this->block);
        $this->block->expects($this->once())
            ->method('setActive')->with('Magento_Reminder::promo_reminder')->willReturn($this->block);
        $this->block->expects($this->once())->method('getMenuModel')->willReturn($this->menuModel);
        $this->item->expects($this->once())->method('getTitle')->willReturn('title');
        $this->menuModel->expects($this->once())->method('getParentItems')->willReturn([$this->item]);
        $this->view->expects($this->any())->method('getPage')->willReturn($this->page);
        $this->page->expects($this->any())->method('getConfig')->willReturn($this->config);
        $this->config->expects($this->any())->method('getTitle')->willReturn($this->titleMock);
        $this->titleMock->expects($this->any())->method('prepend')->willReturn('title');
        $this->view->expects($this->once())->method('renderLayout');

        $this->indexController->execute();
    }
}
