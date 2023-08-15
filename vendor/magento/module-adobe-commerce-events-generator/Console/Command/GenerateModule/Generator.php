<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Console\Command\GenerateModule;

use Exception;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventCode\EventCodeSupportedValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeCommerceEventsGenerator\Generator\Collector\ApiServiceCollector;
use Magento\AdobeCommerceEventsGenerator\Generator\Collector\CollectorException;
use Magento\AdobeCommerceEventsGenerator\Generator\Collector\ModuleCollector;
use Magento\AdobeCommerceEventsGenerator\Generator\Collector\ResourceModelCollector;
use Magento\AdobeCommerceEventsGenerator\Generator\Module;
use Magento\AdobeCommerceEventsGenerator\Generator\ModuleGenerator;
use Magento\AdobeCommerceEventsGenerator\Generator\PluginConverter;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Event\ManagerInterface;

/**
 * Generates Adobe Commerce module with plugins for subscribed event codes
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Generator
{
    public const OBSERVER_EVENT_INTERFACE = ManagerInterface::class;
    public const OBSERVER_EVENT_METHOD = "dispatch";

    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var ModuleGenerator
     */
    private ModuleGenerator $moduleGenerator;

    /**
     * @var PluginConverter
     */
    private PluginConverter $pluginConverter;

    /**
     * @var ApiServiceCollector
     */
    private ApiServiceCollector $apiServiceCollector;

    /**
     * @var ResourceModelCollector
     */
    private ResourceModelCollector $resourceModelCollector;

    /**
     * @var ModuleCollector
     */
    private ModuleCollector $moduleCollector;

    /**
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * @var EventCodeSupportedValidator|null
     */
    private ?EventCodeSupportedValidator $eventCodeSupportedValidator = null;

    /**
     * @param EventList $eventList
     * @param ModuleGenerator $moduleGenerator
     * @param ApiServiceCollector $apiServiceCollector
     * @param ResourceModelCollector $resourceModelCollector
     * @param PluginConverter $pluginConverter
     * @param ModuleCollector $moduleCollector
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        EventList $eventList,
        ModuleGenerator $moduleGenerator,
        ApiServiceCollector $apiServiceCollector,
        ResourceModelCollector $resourceModelCollector,
        PluginConverter $pluginConverter,
        ModuleCollector $moduleCollector,
        ObjectManagerInterface $objectManager
    ) {
        $this->moduleGenerator = $moduleGenerator;
        $this->apiServiceCollector = $apiServiceCollector;
        $this->resourceModelCollector = $resourceModelCollector;
        $this->pluginConverter = $pluginConverter;
        $this->eventList = $eventList;
        $this->moduleCollector = $moduleCollector;
        $this->objectManager = $objectManager;
    }

    /**
     * Runs the module generating.
     *
     * @param string $outputDir
     * @throws Exception
     */
    public function run(string $outputDir)
    {
        $errors = [];

        $apiInterfaces = $resourceModels = $observerEvents = $visitedEvents = [];

        foreach ($this->eventList->getAll() as $event) {
            try {
                $this->getEventCodeSupportedValidator()->validate($event);
                $eventName = $event->getParent() ?? $event->getName();
                $eventName = $this->eventList->removeCommercePrefix($eventName);
                if (in_array($eventName, $visitedEvents)) {
                    continue;
                }
                $eventNameParts = explode('.', $eventName);
                if ($eventNameParts[0] === EventSubscriberInterface::EVENT_TYPE_PLUGIN) {
                    if (strpos($eventName, 'resource_model') !== false) {
                        $resourceModels = array_merge_recursive(
                            $resourceModels,
                            $this->resourceModelCollector->collect($eventName)
                        );
                    } else {
                        $apiInterfaces = array_merge_recursive(
                            $apiInterfaces,
                            $this->apiServiceCollector->collect($eventName)
                        );
                    }
                } elseif ($eventNameParts[0] === EventSubscriberInterface::EVENT_TYPE_OBSERVER) {
                    $observerEvents[$eventName] = $eventName;
                } else {
                    $errors[] = sprintf(
                        'The specified event, has an invalid prefix: "%s". The prefix must be %s or %s.',
                        $eventNameParts[0],
                        EventSubscriberInterface::EVENT_TYPE_PLUGIN,
                        EventSubscriberInterface::EVENT_TYPE_OBSERVER
                    );
                }
                $visitedEvents[] = $eventName;
            } catch (CollectorException|ValidatorException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new Exception(
                'Can not execute generation for some event codes:' . PHP_EOL . implode(PHP_EOL, $errors)
            );
        }

        $module = new Module(ModuleGenerator::MODULE_VENDOR, ModuleGenerator::MODULE_NAME);
        $plugins = array_merge(
            $this->pluginConverter->convert($apiInterfaces, PluginConverter::TYPE_API_INTERFACE),
            $this->pluginConverter->convert($resourceModels, PluginConverter::TYPE_RESOURCE_MODEL)
        );
        $module->setPlugins($plugins);
        $module->setDependencies($this->moduleCollector->getModules());

        $observerInterface = [
            self::OBSERVER_EVENT_INTERFACE => [
                ['name' => self::OBSERVER_EVENT_METHOD]
            ]
        ];
        $module->setObserverEventPlugin($this->pluginConverter->convert($observerInterface)[0]);
        $module->setObserverEvents($observerEvents);

        $this->moduleGenerator->setOutputDir($outputDir);
        $this->moduleGenerator->run($module, null);
    }

    /**
     * This is a workaround as this class is a part of CLI command which must work on not installed application.
     * The dependency EventCodeSupportedValidator can not be added via constructor because it has a dependency
     * on the interface which can not be resolved on uninstalled magento without enabled modules.
     *
     * @return EventCodeSupportedValidator
     */
    private function getEventCodeSupportedValidator(): EventCodeSupportedValidator
    {
        if ($this->eventCodeSupportedValidator === null) {
            $this->eventCodeSupportedValidator = $this->objectManager->get(EventCodeSupportedValidator::class);
        }

        return $this->eventCodeSupportedValidator;
    }
}
