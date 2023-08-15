<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Update\Grid;

use Magento\Staging\Model\Update\Grid\ActionsDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionsDataProviderTest extends TestCase
{
    /**
     * @var ActionsDataProvider|MockObject
     */
    private $action;

    public function testGetActionData()
    {
        $actionsList = $this->getActionDataDataProvider();
        $model = new ActionsDataProvider($actionsList);

        $this->action->expects($this->exactly(count($actionsList)))
            ->method('getActionData')
            ->willReturn(['']);

        $model->getActionData([]);
    }

    /**
     * @return array
     */
    public function getActionDataDataProvider()
    {
        return [
            'deleteAction' => $this->getActionStub(),
            'editAction' => $this->getActionStub()
        ];
    }

    public function testGetActionDataWithException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $actionsList = $this->getActionDataWithExceptionDataProvider();
        $model = new ActionsDataProvider($actionsList);

        $this->action->expects($this->once())
            ->method('getActionData')
            ->withAnyParameters()
            ->willReturn([]);

        $model->getActionData();
    }

    /**
     * @return array
     */
    public function getActionDataWithExceptionDataProvider()
    {
        return [
            'deleteAction' => $this->getActionStub(),
            'dummyWrongAction' => 'just some dummy action'
        ];
    }

    /**
     * @return MockObject
     */
    private function getActionStub()
    {
        if ($this->action === null) {
            $this->action = $this->getMockBuilder(ActionsDataProvider::class)
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->action;
    }
}
