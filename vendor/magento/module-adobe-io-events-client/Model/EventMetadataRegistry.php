<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model;

use Magento\AdobeIoEventsClient\Api\EventMetadataRegistryInterface;
use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Model\Data\EventMetadataFactory;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Provides access to the metadata configuration
 */
class EventMetadataRegistry implements EventMetadataRegistryInterface
{
    public const PATH_TO_IO_EVENTS_DECLARATION = "app/etc/event-types.json";

    /**
     * @var EventMetadataFactory
     */
    private EventMetadataFactory $eventMetadataFactory;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var ReadFactory
     */
    private ReadFactory $readFactory;

    /**
     * @var EventProviderFactory
     */
    private EventProviderFactory $eventProviderFactory;

    /**
     * @var array|null
     */
    private ?array $data = null;

    /**
     * @param EventMetadataFactory $eventMetadataFactory
     * @param Json $json
     * @param ReadFactory $readFactory
     * @param EventProviderFactory $eventProviderFactory
     */
    public function __construct(
        EventMetadataFactory $eventMetadataFactory,
        Json $json,
        ReadFactory $readFactory,
        EventProviderFactory $eventProviderFactory
    ) {
        $this->eventMetadataFactory = $eventMetadataFactory;
        $this->json = $json;
        $this->readFactory = $readFactory;
        $this->eventProviderFactory = $eventProviderFactory;
    }

    /**
     * @inheritDoc
     */
    public function getDeclaredEventProvider(): EventProviderInterface
    {
        $this->loadData();

        $provider = $this->eventProviderFactory->create();
        $provider->setData($this->data["provider"]);

        return $provider;
    }

    /**
     * @inheritDoc
     */
    public function getDeclaredEventMetadataList(): array
    {
        $this->loadData();

        $eventTypes = [];

        foreach ($this->data['events'] as $et) {
            $eventType = $this->eventMetadataFactory->create(["data" => $et ]);

            $eventTypes[] =  $eventType;
        }

        return $eventTypes;
    }

    /**
     * Load Data from the configuration file
     *
     * @return void
     */
    private function loadData(): void
    {
        if ($this->data === null) {
            $file = $this->readFactory->create(self::PATH_TO_IO_EVENTS_DECLARATION, DriverPool::FILE);
            $this->data = $this->json->unserialize($file->readAll());
        }
    }
}
