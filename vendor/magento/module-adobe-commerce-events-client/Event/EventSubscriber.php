<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Exception;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\Framework\Config\File\ConfigFilePool;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class EventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Writer
     */
    private Writer $configWriter;

    /**
     * @var DeploymentConfig
     */
    private DeploymentConfig $deploymentConfig;

    /**
     * @var EventValidatorInterface
     */
    private EventValidatorInterface $subscribeValidator;

    /**
     * @var EventValidatorInterface
     */
    private EventValidatorInterface $unsubscribeValidator;

    /**
     * @var EventMetadataClient
     */
    private EventMetadataClient $metadataClient;

    /**
     * @var AdobeIoEventMetadataFactory
     */
    private AdobeIoEventMetadataFactory $ioMetadataFactory;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Writer $configWriter
     * @param DeploymentConfig $deploymentConfig
     * @param EventValidatorInterface $subscribeValidator
     * @param EventValidatorInterface $unsubscribeValidator
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param AdobeIoEventMetadataFactory $eventMetadataFactory
     * @param EventMetadataClient $metadataClient
     * @param EventList $eventList
     * @param LoggerInterface $logger
     */
    public function __construct(
        Writer $configWriter,
        DeploymentConfig $deploymentConfig,
        EventValidatorInterface $subscribeValidator,
        EventValidatorInterface $unsubscribeValidator,
        AdobeIOConfigurationProvider $configurationProvider,
        AdobeIoEventMetadataFactory $eventMetadataFactory,
        EventMetadataClient $metadataClient,
        EventList $eventList,
        LoggerInterface $logger
    ) {
        $this->configWriter = $configWriter;
        $this->deploymentConfig = $deploymentConfig;
        $this->subscribeValidator = $subscribeValidator;
        $this->unsubscribeValidator = $unsubscribeValidator;
        $this->configurationProvider = $configurationProvider;
        $this->ioMetadataFactory = $eventMetadataFactory;
        $this->metadataClient = $metadataClient;
        $this->eventList = $eventList;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function subscribe(Event $event, bool $force = false): void
    {
        $this->subscribeValidator->validate($event, $force);

        try {
            $this->metadataClient->createEventMetadata(
                $this->configurationProvider->getProvider(),
                $this->ioMetadataFactory->generate(self::EVENT_PREFIX_COMMERCE . $event->getName())
            );

            $ioEvents = $this->deploymentConfig->get(self::IO_EVENTS_CONFIG_NAME, []);

            $ioEvents[$event->getName()] = $this->convertEventToConfig($event);
            $this->configWriter->saveConfig(
                [
                    ConfigFilePool::APP_CONFIG => [
                        self::IO_EVENTS_CONFIG_NAME => $ioEvents
                    ]
                ],
                true
            );
            $this->eventList->reset();

            $this->logger->info(sprintf('Event subscription %s was added', $event->getName()));
        } catch (Exception $e) {
            throw new ValidatorException(__($e->getMessage()), $e, $e->getCode());
        }
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(Event $event): void
    {
        $this->unsubscribeValidator->validate($event);

        try {
            $this->metadataClient->deleteEventMetadata(
                $this->configurationProvider->getProvider(),
                $this->ioMetadataFactory->generate(self::EVENT_PREFIX_COMMERCE . $event->getName())
            );

            $this->configWriter->saveConfig([
                ConfigFilePool::APP_CONFIG => [
                    self::IO_EVENTS_CONFIG_NAME => [
                        $event->getName() => [Event::EVENT_ENABLED => 0]
                    ]
                ]
            ]);
            $this->eventList->reset();

            $this->logger->info(sprintf('Subscription to event %s was removed', $event->getName()));
        } catch (Exception $e) {
            throw new ValidatorException(__($e->getMessage()), $e, $e->getCode());
        }
    }

    /**
     * Converts Event object to the configuration array
     *
     * @param Event $event
     * @return array
     */
    private function convertEventToConfig(Event $event): array
    {
        $eventData = [
            Event::EVENT_FIELDS => $event->getFields(),
            Event::EVENT_ENABLED => 1
        ];
        if (!empty($event->getRules())) {
            $eventData[Event::EVENT_RULES] = $event->getRules();
        }
        if (!empty($event->getParent())) {
            $eventData[Event::EVENT_PARENT] = $event->getParent();
        }
        if ($event->isPriority()) {
            $eventData[Event::EVENT_PRIORITY] = 1;
        }

        return $eventData;
    }
}
