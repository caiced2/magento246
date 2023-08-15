<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

use Magento\TargetRule\Controller\Adminhtml\Targetrule\NewAction;

class NewActionTest extends AbstractTest
{
    /**
     * @var NewAction
     */
    protected $controller;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new NewAction(
            $this->contextMock,
            $this->registryMock,
            $this->dateMock
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock
            ->expects($this->atLeastOnce())
            ->method('setDispatched')
            ->with(false);
        $this->requestMock
            ->expects($this->atLeastOnce())
            ->method('setActionName')
            ->with('edit');

        $this->controller->execute();
    }
}
