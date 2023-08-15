<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Console;

use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\EventMetadataRegistry;
use Magento\AdobeIoEventsClient\Model\EventProviderClient;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to create an event provider
 */
class CreateEventProvider extends Command
{
    public const COMMAND_NAME = 'events:create-event-provider';

    public const OPTION_PROVIDER_LABEL = 'label';
    public const OPTION_PROVIDER_DESCRIPTION = 'description';

    private const RETURN_SUCCESS = 0;
    private const RETURN_FAILURE = 1;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var EventMetadataRegistry
     */
    private EventMetadataRegistry $eventMetadataRegistry;

    /**
     * @var EventProviderClient
     */
    private EventProviderClient $eventProviderClient;

    /**
     * @var EventProviderFactory
     */
    private EventProviderFactory $eventProviderFactory;

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param EventMetadataRegistry $eventMetadataRegistry
     * @param EventProviderClient $eventProviderClient
     * @param EventProviderFactory $eventProviderFactory
     */
    public function __construct(
        AdobeIOConfigurationProvider $configurationProvider,
        EventMetadataRegistry $eventMetadataRegistry,
        EventProviderClient $eventProviderClient,
        EventProviderFactory $eventProviderFactory
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->eventMetadataRegistry = $eventMetadataRegistry;
        $this->eventProviderClient = $eventProviderClient;
        $this->eventProviderFactory = $eventProviderFactory;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(
            "Create a custom event provider in Adobe I/O Events for this instance. ".
            "If you do not specify the label and description options, they must be defined in the " .
            "system " . EventMetadataRegistry::PATH_TO_IO_EVENTS_DECLARATION . " file."
        );
        $this->setAliases(['events:provider:create ']);
        $this->addOption(
            self::OPTION_PROVIDER_LABEL,
            null,
            InputOption::VALUE_OPTIONAL,
            'A label to define your custom provider.'
        );
        $this->addOption(
            self::OPTION_PROVIDER_DESCRIPTION,
            null,
            InputOption::VALUE_OPTIONAL,
            'A description of your provider.'
        );

        parent::configure();
    }

    /**
     * @inheritDoc
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = $this->configurationProvider->getProvider();
        $instanceId = $this->configurationProvider->retrieveInstanceId();

        if ($provider !== null) {
            $output->writeln("Already found an event provider configured with ID " . $provider->getId());
            return self::RETURN_FAILURE;
        }

        $output->writeln("No event provider found, a new event provider will be created");

        try {
            $provider = $this->eventProviderFactory->create(['data' => $this->eventProviderClient->createEventProvider(
                $instanceId,
                $this->getProvider($input)
            )]);
        } catch (LocalizedException $exception) {
            $output->writeln(sprintf(
                '<error>%s</error>',
                $exception->getMessage()
            ));

            return self::RETURN_FAILURE;
        }

        $this->configurationProvider->saveProvider($provider);
        $output->writeln("A new event provider has been created with ID " . $provider->getId());

        return self::RETURN_SUCCESS;
    }

    /**
     * Creates provider object.
     * If input option provider-label is not empty than creates provider from options otherwise creates
     * provider from the configuration file EventMetadataRegistry::PATH_TO_IO_EVENTS_DECLARATION
     *
     * @param InputInterface $input
     * @return EventProviderInterface
     */
    private function getProvider(InputInterface $input): EventProviderInterface
    {
        $providerLabel = $input->getOption(self::OPTION_PROVIDER_LABEL);
        if (!empty($providerLabel)) {
            $provider = $this->eventProviderFactory->create([
                'data' => [
                    'label' => $providerLabel,
                    'description' => $input->getOption(self::OPTION_PROVIDER_DESCRIPTION)
                ]
            ]);
        } else {
            $provider = $this->eventMetadataRegistry->getDeclaredEventProvider();
        }

        return $provider;
    }
}
