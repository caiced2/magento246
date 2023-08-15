<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftRegistry\Model\Logging;
use Magento\Logging\Model\Event;
use Magento\Logging\Model\Event\Changes;
use Magento\Logging\Model\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoggingTest extends TestCase
{
    /**
     * @var Logging
     */
    protected $logging;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestInterface;

    /**
     * @var Event|MockObject
     */
    protected $eventModel;

    /**
     * @var Processor|MockObject
     */
    protected $processor;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->requestInterface = $this->getMockForAbstractClass(RequestInterface::class);
        $this->eventModel = $this->getMockBuilder(Event::class)
            ->onlyMethods(['__sleep'])
            ->addMethods(['setInfo'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->logging = $this->objectManagerHelper->getObject(
            Logging::class,
            [
                'request' => $this->requestInterface
            ]
        );
    }

    /**
     * @return void
     */
    public function testPostDispatchTypeSave(): void
    {
        $this->requestInterface->expects($this->once())->method('getParam')
            ->with('type')
            ->willReturn(['type_id' => 'Some Type Id']);
        $this->eventModel->expects($this->once())->method('setInfo')->with('Some Type Id')->willReturnSelf();
        $this->logging->postDispatchTypeSave([], $this->eventModel, $this->processor);
    }

    /**
     * @return void
     */
    public function testPostDispatchShare(): void
    {
        $this->requestInterface
            ->method('getParam')
            ->withConsecutive(['emails'], ['message'])
            ->willReturnOnConsecutiveCalls(['some@example.com'], 'Gift Registry Message');

        $changes = $this->getMockBuilder(Changes::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor->expects($this->any())->method('createChanges')->willReturn($changes);
        $this->processor->expects($this->any())->method('addEventChanges')->with($changes)->willReturnSelf();

        $event = $this->logging->postDispatchShare([], $this->eventModel, $this->processor);
        $this->assertSame($this->eventModel, $event);
    }
}
