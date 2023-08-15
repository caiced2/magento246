<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\Event;

use Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\NewAction;
use Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\AbstractEventTest;

class NewActionTest extends AbstractEventTest
{
    /**
     * @var NewAction
     */
    protected $newAction;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->newAction = new NewAction(
            $this->contextMock,
            $this->registryMock,
            $this->eventFactoryMock,
            $this->dateTimeMock,
            $this->storeManagerMock
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock
            ->expects($this->once())
            ->method('setActionName')
            ->with('edit');
        $this->requestMock
            ->expects($this->once())
            ->method('setDispatched')
            ->with(false);

        $this->newAction->execute();
    }
}
