<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

use Magento\TargetRule\Controller\Adminhtml\Targetrule\Grid;

class GridTest extends AbstractTest
{
    /**
     * @var Grid
     */
    protected $controller;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new Grid(
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
        $this->viewMock
            ->expects($this->once())
            ->method('loadLayout')
            ->with(false);

        $this->viewMock
            ->expects($this->once())
            ->method('renderLayout');

        $this->controller->execute();
    }
}
