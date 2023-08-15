<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Test\Unit\Console\Command\GenerateModule;

use Exception;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventCode\EventCodeSupportedValidator;
use Magento\AdobeCommerceEventsGenerator\Console\Command\GenerateModule\Generator;
use Magento\AdobeCommerceEventsGenerator\Generator\Collector\ApiServiceCollector;
use Magento\AdobeCommerceEventsGenerator\Generator\Collector\ModuleCollector;
use Magento\AdobeCommerceEventsGenerator\Generator\PluginConverter;
use Magento\AdobeCommerceEventsGenerator\Generator\ModuleGenerator;
use Magento\AdobeCommerceEventsGenerator\Generator\Collector\ResourceModelCollector;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Generator class.
 */
class GeneratorTest extends TestCase
{
    /**
     * @var EventList|MockObject
     */
    private $eventListMock;

    /**
     * @var ModuleGenerator|MockObject
     */
    private $moduleGeneratorMock;

    /**
     * @var ApiServiceCollector|MockObject
     */
    private $apiServiceCollectorMock;

    /**
     * @var ResourceModelCollector|MockObject
     */
    private $resourceModelCollectorMock;

    /**
     * @var PluginConverter|MockObject
     */
    private $pluginConverterMock;

    /**
     * @var ModuleCollector|MockObject
     */
    private $moduleCollectorMock;

    /**
     * @var EventCodeSupportedValidator|MockObject
     */
    private $eventCodeSupportedValidatorMock;

    /**
     * @var Generator
     */
    private Generator $generator;

    protected function setUp(): void
    {
        $this->eventListMock = $this->createMock(EventList::class);
        $this->moduleGeneratorMock = $this->createMock(ModuleGenerator::class);
        $this->apiServiceCollectorMock = $this->createMock(ApiServiceCollector::class);
        $this->resourceModelCollectorMock = $this->createMock(ResourceModelCollector::class);
        $this->pluginConverterMock = $this->createMock(PluginConverter::class);
        $this->moduleCollectorMock = $this->createMock(ModuleCollector::class);
        $this->eventCodeSupportedValidatorMock = $this->createMock(EventCodeSupportedValidator::class);
        $objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $objectManagerMock->expects(self::any())
            ->method('get')
            ->willReturn($this->eventCodeSupportedValidatorMock);
        $this->generator = new Generator(
            $this->eventListMock,
            $this->moduleGeneratorMock,
            $this->apiServiceCollectorMock,
            $this->resourceModelCollectorMock,
            $this->pluginConverterMock,
            $this->moduleCollectorMock,
            $objectManagerMock
        );
    }

    /**
     * Checks that event and plugin information is correctly processed before the generated plugin module is created.
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRun(): void
    {
        $resourceModelEventName = 'plugin.resource_model_event';
        $resourceModelCollection = ['ResourceModelClass'];
        $resourceModelPlugins = [
            ['name' => 'ResourceModelClassPlugin']
        ];

        $apiEventName = 'plugin.api_event';
        $apiInterfaceCollection = ['ApiInterface'];
        $apiInterfacePlugins = [
            ['name' => 'ApiInterfacePlugin']
        ];

        $observerEventName = 'observer.event';
        $observerEventPlugin = [
            ['name' => 'ManagerInterfacePlugin']
        ];
        $observerEvents = [
            $observerEventName => $observerEventName
        ];

        $plugins = [$apiInterfacePlugins[0], $resourceModelPlugins[0]];
        $dependencies = [
            'magento/module-test' => [
                'packageName' => 'magento/module-test',
            ]
        ];
        $outputDirectory = "./outputDir";

        // Set expectations for test Events.
        $resourceModelEventMock = $this->createMock(Event::class);
        $resourceModelEventMock->expects(self::once())
            ->method('getName')
            ->willReturn(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $resourceModelEventName);
        $apiEventMock = $this->createMock(Event::class);
        $apiEventMock->expects(self::once())
            ->method('getName')
            ->willReturn(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $apiEventName);
        $observerEventMock = $this->createMock(Event::class);
        $observerEventMock->expects(self::once())
            ->method('getName')
            ->willReturn(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $observerEventName);
        $this->eventListMock->expects(self::exactly(3))
            ->method('removeCommercePrefix')
            ->withConsecutive(
                [EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $resourceModelEventName],
                [EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $apiEventName],
                [EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $observerEventName],
            )
            ->willReturnOnConsecutiveCalls(
                $resourceModelEventName,
                $apiEventName,
                $observerEventName,
            );
        $this->eventCodeSupportedValidatorMock->expects(self::exactly(3))
            ->method('validate');

        // Set expectations for calls to methods of classes inheriting from CollectorInterface.
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$resourceModelEventMock, $apiEventMock, $observerEventMock]);
        $this->resourceModelCollectorMock->expects(self::once())
            ->method('collect')
            ->with($resourceModelEventName)
            ->willReturn($resourceModelCollection);
        $this->apiServiceCollectorMock->expects(self::once())
            ->method('collect')
            ->with($apiEventName)
            ->willReturn($apiInterfaceCollection);

        // Set expectations for calls to convert method of PluginConverter class.
        $this->pluginConverterMock->expects(self::exactly(3))
            ->method('convert')
            ->withConsecutive(
                [$apiInterfaceCollection, PluginConverter::TYPE_API_INTERFACE],
                [$resourceModelCollection, PluginConverter::TYPE_RESOURCE_MODEL],
                [$this->arrayHasKey(Generator::OBSERVER_EVENT_INTERFACE)]
            )
            ->willReturnOnConsecutiveCalls(
                $apiInterfacePlugins,
                $resourceModelPlugins,
                $observerEventPlugin
            );

        $this->moduleCollectorMock->expects(self::once())
            ->method('getModules')
            ->willReturn($dependencies);

        $this->moduleGeneratorMock->expects(self::once())
            ->method('setOutputDir')
            ->with($outputDirectory);

        // Set expectations for setup of Module passed to the ModuleGenerator's run method.
        $this->moduleGeneratorMock->expects(self::once())
            ->method('run')
            ->with(
                $this->callback(
                    function ($module) use (
                        $plugins,
                        $dependencies,
                        $observerEventPlugin,
                        $observerEvents
                    ) {
                        $this->assertEquals($plugins, $module->getPlugins());
                        $this->assertEquals($dependencies, $module->getDependencies());
                        $this->assertEquals($observerEventPlugin[0], $module->getObserverEventPlugin());
                        $this->assertEquals($observerEvents, $module->getObserverEvents());
                        return true;
                    }
                ),
                null
            );
        $this->generator->run($outputDirectory);
    }

    /**
     * Checks that the 'parent' event code is used for an Event's plugin generation when it is not null.
     * Checks that collector doesn't run two times for the same event.
     *
     * @return void
     */
    public function testRunWithParentEventCode()
    {
        $parentEventName = 'plugin.resource_model_event';
        $observerEventPlugin = [
            ['name' => 'ManagerInterfacePlugin']
        ];

        // Set expectations for test Event
        $eventMockOne = $this->createMock(Event::class);
        $eventMockOne->expects(self::never())
            ->method('getName');
        $eventMockOne->expects(self::once())
            ->method('getParent')
            ->willReturn(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $parentEventName);
        $eventMockTwo = $this->createMock(Event::class);
        $eventMockTwo->expects(self::once())
            ->method('getName')
            ->willReturn(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $parentEventName);
        $eventMockTwo->expects(self::once())
            ->method('getParent')
            ->willReturn(null);

        $this->eventListMock->expects(self::exactly(2))
            ->method('removeCommercePrefix')
            ->with(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $parentEventName)
            ->willReturn($parentEventName);
        $this->eventCodeSupportedValidatorMock->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive(
                [$eventMockOne],
                [$eventMockTwo]
            );

        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMockOne, $eventMockTwo]);
        $this->resourceModelCollectorMock->expects(self::once())
            ->method('collect')
            ->with($parentEventName);

        // This method is mocked just to prevent tests from failing after meaningful expectations are checked.
        $this->pluginConverterMock->expects(self::exactly(3))
            ->method('convert')
            ->willReturn($observerEventPlugin);

        $this->generator->run('./output');
    }

    /**
     * Checks that an exception is thrown when an event with an unknown event code prefix is processed.
     *
     * @return void
     */
    public function testRunInvalidEventCode(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The specified event, has an invalid prefix: "unknown"');

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . 'unknown.event');
        $this->eventListMock->expects(self::once())
            ->method('removeCommercePrefix')
            ->with(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . 'unknown.event')
            ->willReturn('unknown.event');
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);

        $this->generator->run("output");
    }
}
