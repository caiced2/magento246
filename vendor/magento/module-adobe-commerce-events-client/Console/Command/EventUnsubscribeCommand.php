<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Console\Command;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeIoEventsClient\Console\CreateEventProvider;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Command for unsubscribing from events
 */
class EventUnsubscribeCommand extends Command
{
    private const ARGUMENT_EVENT_CODE = 'event-code';

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var EventSubscriberInterface
     */
    private EventSubscriberInterface $eventSubscriber;

    /**
     * @var EventFactory
     */
    private EventFactory $eventFactory;

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param EventSubscriberInterface $eventSubscriber
     * @param EventFactory $eventFactory
     */
    public function __construct(
        AdobeIOConfigurationProvider $configurationProvider,
        EventSubscriberInterface $eventSubscriber,
        EventFactory $eventFactory
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->eventSubscriber = $eventSubscriber;
        $this->eventFactory = $eventFactory;
        parent::__construct('events:unsubscribe');
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Removes the subscription to the supplied event')
            ->addArgument(
                self::ARGUMENT_EVENT_CODE,
                InputArgument::REQUIRED,
                'Event code to unsubscribe from'
            );

        parent::configure();
    }

    /**
     * Removes the subscription to the event.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $eventCode = $input->getArgument(self::ARGUMENT_EVENT_CODE);
        try {
            if (!$this->configurationProvider->isConfigured()) {
                $output->writeln(
                    sprintf(
                        "<error>No event provider is configured, please run bin/magento %s</error>",
                        CreateEventProvider::COMMAND_NAME
                    )
                );
                return Cli::RETURN_FAILURE;
            }

            $this->eventSubscriber->unsubscribe(
                $this->eventFactory->create([Event::EVENT_NAME => $eventCode])
            );
            $output->writeln(sprintf("Successfully unsubscribed from the '%s' event", $eventCode));
        } catch (Throwable $e) {
            $output->writeln("<error>Error unsubscribing from event '$eventCode': {$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
