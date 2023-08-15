<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Console;

use Magento\AdobeIoEventsClient\Api\EventMetadataRegistryInterface;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to synchronize event metadata
 */
class SynchronizeEventMetadata extends Command
{
    private const RETURN_SUCCESS = 0;
    private const RETURN_FAILURE = 1;

    /**
     * @var EventMetadataRegistryInterface
     */
    private EventMetadataRegistryInterface $eventRegistry;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var EventMetadataClient
     */
    private EventMetadataClient $eventMetadataClient;

    /**
     * @param EventMetadataRegistryInterface $eventRegistry
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param EventMetadataClient $eventMetadataClient
     */
    public function __construct(
        EventMetadataRegistryInterface $eventRegistry,
        AdobeIOConfigurationProvider $configurationProvider,
        EventMetadataClient $eventMetadataClient
    ) {
        $this->eventRegistry = $eventRegistry;
        $this->configurationProvider = $configurationProvider;
        $this->eventMetadataClient = $eventMetadataClient;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName("events:sync-events-metadata");
        $this->setDescription("Synchronise event metadata for this instance");

        $this->addOption(
            "delete",
            "d",
            InputOption::VALUE_NONE,
            "Delete events metadata no longer required"
        );

        parent::configure();
    }

    /**
     * @inheritDoc
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws InputException
     * @throws InvalidConfigurationException
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = $this->configurationProvider->getProvider();
        if ($provider === null) {
            $output->writeln(
                sprintf(
                    "<error>No event provider is configured, please run bin/magento %s</error>",
                    CreateEventProvider::COMMAND_NAME
                )
            );
            return self::RETURN_FAILURE;
        }

        $output->writeln(
            sprintf(
                "Event provider with ID <info>%s</info> retrieved from configuration",
                $provider->getId()
            )
        );

        $output->writeln("<info>The following events are declared on your instance:</info>");
        $declaredEventMetadata = $this->eventRegistry->getDeclaredEventMetadataList();
        foreach ($declaredEventMetadata as $eventMetadata) {
            $output->writeln("- $eventMetadata");
        }

        $registeredEventMetadata = $this->eventMetadataClient->listRegisteredEventMetadata($provider);

        $eventTypeToDelete = array_diff($registeredEventMetadata, $declaredEventMetadata);

        $output->writeln("<info>Updating event types:</info>");
        foreach ($declaredEventMetadata as $eventType) {
            $this->eventMetadataClient->createEventMetadata($provider, $eventType);
            $output->writeln("- <info>[UPDATED]</info> $eventType");
        }

        if (count($eventTypeToDelete) > 0) {
            if ($input->getOption("delete")) {
                $output->writeln("<info>Delete the following event metedata:</info>");
                foreach ($eventTypeToDelete as $eventType) {
                    $deleted = $this->eventMetadataClient->deleteEventMetadata(
                        $provider,
                        $eventType
                    );
                    if ($deleted) {
                        $output->writeln("- <comment>[DELETED]</comment> $eventType");
                    } else {
                        $output->writeln("- <error>[FAILURE]</error> $eventType");
                    }
                }
            } else {
                $output->writeln(
                    "<info>The following event metadata could be deleted, by using --delete option</info>"
                );
                foreach ($eventTypeToDelete as $eventType) {
                    $output->writeln("- $eventType");
                }
            }
        }

        return self::RETURN_SUCCESS;
    }
}
