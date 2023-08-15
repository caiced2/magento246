<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Test\Unit\App\Action\Plugin;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Logging\App\Action\Plugin\Log;
use Magento\Logging\Model\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    /**
     * @var string
     */
    private $actionName = 'taction';

    /**
     * @var Processor|MockObject
     */
    private $processorMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var Log
     */
    private $model;

    /**
     * @var ActionInterface|MockObject
     */
    private $subjectMock;

    protected function setUp(): void
    {
        $this->processorMock = $this->createMock(Processor::class);

        $this->requestMock = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getBeforeForwardInfo', 'getFullActionName', 'getRouteName', 'getControllerName']
        );
        $this->requestMock->expects($this->once())->method('getActionName')->willReturn($this->actionName);

        $this->subjectMock = $this->getMockForAbstractClass(ActionInterface::class, [], '', false, false, true);

        $this->model = (new ObjectManagerHelper($this))
            ->getObject(Log::class, ['processor' => $this->processorMock]);
    }

    public function testBeforeDispatchWithoutForward()
    {
        $fullActionName = 'tmodule_tcontroller_taction';

        $this->requestMock->expects($this->once())->method('getFullActionName')
            ->willReturn($fullActionName);

        $this->processorMock->expects($this->once())->method('initAction')
            ->with($fullActionName, $this->actionName);

        $this->assertNull($this->model->beforeDispatch($this->subjectMock, $this->requestMock));
    }

    public function testBeforeDispatchWithForward()
    {
        $origRoute = 'origRoute';
        $origController = 'origcontroller';
        $origAction = 'origaction';

        $this->requestMock->expects($this->once())->method('getRouteName')->willReturn($origRoute);
        $this->requestMock->expects($this->once())->method('getBeforeForwardInfo')
            ->willReturn(['controller_name' => $origController, 'action_name' => $origAction]);

        $this->processorMock->expects($this->once())->method('initAction')
            ->with($origRoute . '_' . $origController . '_' . $origAction, $this->actionName);

        $this->assertNull($this->model->beforeDispatch($this->subjectMock, $this->requestMock));
    }

    public function testBeforeDispatchWithForwardAndWithoutOriginalInfo()
    {
        $origRoute = 'origRoute';
        $requestedController = 'requestedController';

        $this->requestMock->expects($this->once())->method('getRouteName')->willReturn($origRoute);
        $this->requestMock->expects($this->once())->method('getControllerName')->willReturn($requestedController);
        $this->requestMock->expects($this->once())->method('getBeforeForwardInfo')->willReturn(['forward']);
        $this->processorMock->expects($this->once())->method('initAction')
            ->with($origRoute . '_' . $requestedController . '_' . $this->actionName, $this->actionName);

        $this->assertNull($this->model->beforeDispatch($this->subjectMock, $this->requestMock));
    }
}
