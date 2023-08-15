<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Event;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\InvokerInterface;
use Magento\Framework\Event\ConfigInterface;
use Magento\Framework\Event\Observer;

class Manager implements ManagerInterface
{
    /**
     * Event invoker
     *
     * @var InvokerInterface
     */
    protected $invoker;

    /**
     * Event config
     *
     * @var ConfigInterface
     */
    protected $eventConfig;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    protected $versionManager;

    /**
     * @var array
     */
    protected $bannedEvents;

    /**
     * @var array
     */
    protected $bannedObservers;

    /**
     * Manager constructor.
     * @param InvokerInterface $invoker
     * @param ConfigInterface $eventConfig
     * @param \Magento\Staging\Model\VersionManagerFactory $versionManagerFactory
     * @param array $bannedEvents
     * @param array $bannedObservers
     */
    public function __construct(
        InvokerInterface $invoker,
        ConfigInterface $eventConfig,
        \Magento\Staging\Model\VersionManagerFactory $versionManagerFactory,
        $bannedEvents = [],
        $bannedObservers = []
    ) {
        $this->invoker = $invoker;
        $this->eventConfig = $eventConfig;
        $this->versionManager = $versionManagerFactory->create();
        $this->bannedEvents = $bannedEvents;
        $this->bannedObservers = $bannedObservers;
    }

    /**
     * Dispatch event
     *
     * Calls all observer callbacks registered for this event
     * and multiple observers matching event name pattern
     *
     * @param string $eventName
     * @param array $data
     * @return void
     */
    public function dispatch($eventName, array $data = [])
    {
        $eventName = mb_strtolower($eventName);

        if (!$this->isAllowedEvent($eventName)) {
            return;
        }

        \Magento\Framework\Profiler::start('EVENT:' . $eventName, ['group' => 'EVENT', 'name' => $eventName]);
        foreach ($this->eventConfig->getObservers($eventName) as $observerConfig) {
            if (!$this->isAllowedObserver($eventName, $observerConfig['name'])) {
                continue;
            }

            $event = new \Magento\Framework\Event($data);
            $event->setName($eventName);

            $wrapper = new Observer();
            $wrapper->setData(array_merge(['event' => $event], $data));

            \Magento\Framework\Profiler::start('OBSERVER:' . $observerConfig['name']);
            $this->invoker->dispatch($observerConfig, $wrapper);
            \Magento\Framework\Profiler::stop('OBSERVER:' . $observerConfig['name']);
        }
        \Magento\Framework\Profiler::stop('EVENT:' . $eventName);
    }

    /**
     * Check whether event allowed or not
     *
     * @param string $eventName
     * @return bool
     */
    protected function isAllowedEvent($eventName)
    {
        if (in_array($eventName, $this->bannedEvents)
            && $this->versionManager->isPreviewVersion()
        ) {
            return false;
        }
        return true;
    }

    /**
     * Check whether observer allowed or not\
     *
     * @param string $eventName
     * @param string $observerName
     * @return bool
     */
    protected function isAllowedObserver($eventName, $observerName)
    {
        if (isset($this->bannedObservers[$eventName])
            && in_array($observerName, $this->bannedObservers[$eventName])
            && $this->versionManager->isPreviewVersion()
        ) {
            return false;
        }
        return true;
    }
}
