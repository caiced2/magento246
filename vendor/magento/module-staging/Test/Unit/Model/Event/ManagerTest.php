<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Event;

use Magento\Framework\Event;
use Magento\Framework\Event\ConfigInterface;
use Magento\Framework\Event\InvokerInterface;
use Magento\Framework\Event\Observer;
use Magento\Staging\Model\Event\Manager;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\VersionManagerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    /**
     * Event invoker
     *
     * @var InvokerInterface|MockObject
     */
    private $invokerMock;

    /**
     * Event config
     *
     * @var ConfigInterface|MockObject
     */
    private $eventConfigMock;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var VersionManagerFactory|MockObject
     */
    private $versionManagerFactoryMock;

    protected function setUp(): void
    {
        $this->invokerMock = $this->getMockBuilder(InvokerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->eventConfigMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerFactoryMock = $this->getMockBuilder(VersionManagerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->versionManagerFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->versionManagerMock
        );
    }

    public function testDispatchAllowed()
    {
        $eventData = ['entity' => new \stdClass()];
        $eventName = 'entity_save';
        $observerConfig = ['name' => 'observer'];
        $manager = new Manager(
            $this->invokerMock,
            $this->eventConfigMock,
            $this->versionManagerFactoryMock
        );
        $this->eventConfigMock->expects($this->once())->method('getObservers')->with($eventName)->willReturn(
            [$observerConfig]
        );
        $event = new Event($eventData);
        $event->setName($eventName);

        $wrapper = new Observer();
        $wrapper->setData(array_merge(['event' => $event], $eventData));
        $this->invokerMock->expects($this->once())->method('dispatch')->with($observerConfig, $wrapper);
        $manager->dispatch($eventName, $eventData);
    }

    public function testDispatchEventDisallowed()
    {
        $eventData = ['entity' => new \stdClass()];
        $eventName = 'entity_save';
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $manager = new Manager(
            $this->invokerMock,
            $this->eventConfigMock,
            $this->versionManagerFactoryMock,
            [$eventName]
        );
        $this->eventConfigMock->expects($this->never())->method('getObservers');
        $this->invokerMock->expects($this->never())->method('dispatch');
        $manager->dispatch($eventName, $eventData);
    }

    public function testDispatchObserverDisallowed()
    {
        $eventData = ['entity' => new \stdClass()];
        $eventName = 'entity_save';
        $observerName = 'observer';
        $observerConfig = ['name' => $observerName];
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $manager = new Manager(
            $this->invokerMock,
            $this->eventConfigMock,
            $this->versionManagerFactoryMock,
            [],
            [$eventName => [$observerName]]
        );
        $this->eventConfigMock->expects($this->once())->method('getObservers')->with($eventName)->willReturn(
            [$observerConfig]
        );
        $this->invokerMock->expects($this->never())->method('dispatch');
        $manager->dispatch($eventName, $eventData);
    }
}
