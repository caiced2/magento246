<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PersistentHistory\Helper\Data;
use Magento\PersistentHistory\Observer\ApplyBlockPersistentDataObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplyBlockPersistentDataObserverTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $persistentHelperMock;

    /**
     * @var MockObject
     */
    protected $observerMock;

    /**
     * @var ApplyBlockPersistentDataObserver
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->persistentHelperMock = $this->createMock(Data::class);
        $this->observerMock = $this->createMock(\Magento\Persistent\Observer\ApplyBlockPersistentDataObserver::class);

        $this->subject = $objectManager->getObject(
            ApplyBlockPersistentDataObserver::class,
            [
                'ePersistentData' => $this->persistentHelperMock,
                'observer' => $this->observerMock,
            ]
        );
    }

    public function testApplyBlockPersistentData()
    {
        $configFilePath = 'file/path';
        $eventObserverMock = $this->createMock(Observer::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['setConfigFilePath'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())
            ->method('setConfigFilePath')
            ->with($configFilePath)->willReturnSelf();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->persistentHelperMock->expects($this->once())
            ->method('getPersistentConfigFilePath')
            ->willReturn($configFilePath);

        $this->observerMock->expects($this->once())
            ->method('execute')
            ->with($eventObserverMock)->willReturnSelf();

        $this->assertEquals($this->observerMock, $this->subject->execute($eventObserverMock));
    }
}
