<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Console\Command;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeCommerceEventsClient\Event\EventInfo;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Command for displaying a payload of the specified event.
 */
class EventInfoCommand extends Command
{
    public const NAME = 'events:info';
    private const ARGUMENT_EVENT_CODE = 'event-code';
    private const OPTION_DEPTH = 'depth';

    /**
     * @var EventInfo
     */
    private EventInfo $eventInfo;

    /**
     * @var EventFactory
     */
    private EventFactory $eventFactory;

    /**
     * @param EventInfo $eventInfo
     * @param EventFactory $eventFactory
     * @param string|null $name
     */
    public function __construct(
        EventInfo $eventInfo,
        EventFactory $eventFactory,
        string $name = null
    ) {
        $this->eventInfo = $eventInfo;
        $this->eventFactory = $eventFactory;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Returns the payload of the specified event.')
            ->addArgument(
                self::ARGUMENT_EVENT_CODE,
                InputArgument::REQUIRED,
                'Event code'
            )
            ->addOption(
                self::OPTION_DEPTH,
                null,
                InputOption::VALUE_OPTIONAL,
                'The number of levels in the event payload to return',
                EventInfo::NESTED_LEVEL
            );

        parent::configure();
    }

    /**
     * Returns the payload of the specified event.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $event = $this->eventFactory->create([
                Event::EVENT_NAME => $input->getArgument(self::ARGUMENT_EVENT_CODE)
            ]);

            $output->writeln(
                $this->eventInfo->getJsonExample(
                    $event,
                    (int)$input->getOption(self::OPTION_DEPTH)
                )
            );
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
