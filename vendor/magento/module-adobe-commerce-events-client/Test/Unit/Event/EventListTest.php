<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Config\Reader;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\Framework\App\DeploymentConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for EventList class
 */
class EventListTest extends TestCase
{
    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var Reader|MockObject
     */
    private $readerMock;

    /**
     * @var DeploymentConfig|MockObject
     */
    private $deploymentConfigMock;

    /**
     * @var EventFactory|MockObject
     */
    private $eventFactoryMock;

    protected function setUp(): void
    {
        $this->readerMock = $this->createMock(Reader::class);
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);
        $this->eventFactoryMock = $this->createMock(EventFactory::class);

        $this->eventList = new EventList(
            $this->readerMock,
            $this->deploymentConfigMock,
            $this->eventFactoryMock
        );
    }

    public function testGetAllXmlConfigOnly()
    {
        $this->readerMock->expects(self::once())
            ->method('read')
            ->willReturn([
                ['name' => 'xml_event1'],
                ['name' => 'xml_event2']
            ]);
        $this->deploymentConfigMock->expects(self::once())
            ->method('get')
            ->with(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME)
            ->willReturn(null);
        $this->eventFactoryMock->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(
                [['name' => 'xml_event1']],
                [['name' => 'xml_event2']]
            );

        $events = $this->eventList->getAll();
        self::assertEquals(2, count($events));
    }

    public function testGetAll()
    {
        $this->mockLoadEvents();

        $events = $this->eventList->getAll();
        self::assertEquals(4, count($events));
    }

    public function testGet()
    {
        $this->mockLoadEvents();

        self::assertInstanceOf(Event::class, $this->eventList->get('xml_event1'));
    }

    public function testGetNotExists()
    {
        $this->mockLoadEvents();

        self::assertNull($this->eventList->get('xml_event_not_exists'));
    }

    public function testEventIsEnabled()
    {
        $this->mockLoadEvents();

        self::assertTrue($this->eventList->isEventEnabled('xml_event1'));
        self::assertFalse($this->eventList->isEventEnabled('xml_event_not_exists'));
        self::assertFalse($this->eventList->isEventEnabled('config_php_event2'));
    }

    private function mockLoadEvents()
    {
        $this->readerMock->expects(self::once())
            ->method('read')
            ->willReturn([
                [
                    'name' => 'xml_event1',
                    'fields' => ['entity_id']
                ],
                [
                    'name' => 'xml_event2',
                    'fields' => ['category_id']
                ]
            ]);
        $this->deploymentConfigMock->expects(self::once())
            ->method('get')
            ->with(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME)
            ->willReturn([
                'config_php_event1' => [
                    'fields' => ['id'],
                    'enabled' => 1
                ],
                'config_php_event2' => [
                    'fields' => ['name'],
                    'enabled' => 0,
                    'priority' => 1
                ],
            ]);
        $this->eventFactoryMock->expects(self::exactly(4))
            ->method('create')
            ->withConsecutive(
                [['name' => 'xml_event1', 'fields' => ['entity_id']]],
                [['name' => 'xml_event2', 'fields' => ['category_id']]],
                [[
                    'name' => 'config_php_event1',
                    'parent' => null,
                    'optional' => true,
                    'enabled' => true,
                    'fields' => ['id'],
                    'rules' => [],
                    'priority' => 0
                ]],
                [[
                    'name' => 'config_php_event2',
                    'parent' => null,
                    'optional' => true,
                    'enabled' => false,
                    'fields' => ['name'],
                    'rules' => [],
                    'priority' => 1
                ]],
            )
            ->willReturn(
                new Event('xml_event1'),
                new Event('xml_event2'),
                new Event('config_php_event1', null, true, true),
                new Event('config_php_event2', null, true, false)
            );
    }
}
