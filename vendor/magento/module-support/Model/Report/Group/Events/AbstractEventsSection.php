<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Support\Model\Report\Group\Events;

use Magento\Framework\App\Area;
use Magento\Framework\Event\Config\Reader;
use Magento\Framework\Event\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Support\Model\Report\Group\AbstractSection;
use Psr\Log\LoggerInterface;
use Laminas\Server\Reflection;

/**
 * Abstract events section
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class AbstractEventsSection extends AbstractSection
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var array
     */
    protected $modules = [
        ConfigInterface::TYPE_CORE => ['Magento', 'Zend'],
    ];

    /**
     * @param LoggerInterface $logger
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(LoggerInterface $logger, ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->reader = $this->objectManager->create(\Magento\Framework\Event\Config\Reader::class);
        parent::__construct($logger);
    }

    /**
     * Return title of section
     *
     * @return string
     */
    abstract public function getTitle();

    /**
     * Specific area code for section
     *
     * @return string
     */
    abstract public function getAreaCode();

    /**
     * Specific application type for section
     *
     * @return string|null
     */
    abstract public function getType();

    /**
     * @inheritDoc
     */
    public function generate()
    {
        $data = $this->getData();
        return [
            $this->getTitle() => [
                'headers' => [(string)__('Event Name'), (string)__('Observer Class'), (string)__('Method')],
                'data' => $data,
                'count' => count($data),
            ],
        ];
    }

    /**
     * Preparing events for current scope
     *
     * @return array
     */
    protected function getEvents()
    {
        $events = [];
        foreach ($this->reader->read($this->getAreaCode()) as $eventName => $observers) {
            foreach ($observers as $observer) {
                if (!(($reflection = $this->getClassReflection($observer)) instanceof Reflection\ReflectionClass)) {
                    continue;
                }

                $instancePathParts = explode('\\', $reflection->getNamespaceName());
                $namespace = reset($instancePathParts);
                $classPath = $this->getClassPath($reflection);

                $events = $this->pushEvent($events, $eventName, $observer, $namespace, $classPath);
            }
        }
        return $events;
    }

    /**
     * Add prepared event data to events scope
     *
     * @param array $events
     * @param string $eventName
     * @param array $observer
     * @param string $namespace
     * @param string $classPath
     * @return array
     */
    public function pushEvent(array $events, $eventName, array $observer, $namespace, $classPath)
    {
        if ($this->isNamespaceRelatedToType($namespace, $this->getType())) {
            $instanceParts = [
                $this->getByKey($observer, 'instance', ''),
                PHP_EOL,
                '{' . $classPath . '}',
            ];
            $events[$eventName][] = [
                'name' => $this->getByKey($observer, 'name', ''),
                'instance' => implode('', $instanceParts),
                'method' => $this->getByKey($observer, 'method', ''),
            ];
        }
        return $events;
    }

    /**
     * Try to reflect class from observer
     *
     * @param array $observer
     * @return null|Reflection\ReflectionClass
     */
    protected function getClassReflection(array $observer)
    {
        if (empty($observer['instance'])) {
            return null;
        }

        try {
            return Reflection::reflectClass($observer['instance']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Prepare class path
     *
     * @codeCoverageIgnore
     * @param Reflection\ReflectionClass $reflection
     * @return string
     */
    protected function getClassPath(Reflection\ReflectionClass $reflection)
    {
        return implode('', ['app', DIRECTORY_SEPARATOR, 'code', DIRECTORY_SEPARATOR, $reflection->getName(), '.php']);
    }

    /**
     * Return prepared data for output
     *
     * @return array
     */
    protected function getData()
    {
        $data = [];
        foreach ($this->getEvents() as $event => $observers) {
            foreach ($observers as $observer) {
                $data[] = [$event, $observer['instance'], $observer['method']];
            }
        }
        return $data;
    }

    /**
     * Check whether namespace is part of type
     *
     * @param string $namespace
     * @param string|null $type
     * @return bool
     */
    public function isNamespaceRelatedToType($namespace, $type)
    {
        $coreModules = $this->modules[ConfigInterface::TYPE_CORE];
        return
            (null === $type)
            || ($type === ConfigInterface::TYPE_CORE && in_array($namespace, $coreModules))
            || ($type === ConfigInterface::TYPE_CUSTOM && !in_array($namespace, $coreModules));
    }
}
