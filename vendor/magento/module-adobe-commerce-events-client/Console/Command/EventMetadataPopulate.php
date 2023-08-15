<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Console\Command;

use Magento\AdobeCommerceEventsClient\Event\Synchronizer\AdobeIoEventMetadataSynchronizer;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Commands for creating events metadata in Adobe I/O from the XML and application configurations
 */
class EventMetadataPopulate extends Command
{
    public const COMMAND_NAME = 'events:metadata:populate';

    /**
     * @var AdobeIoEventMetadataSynchronizer
     */
    private AdobeIoEventMetadataSynchronizer $eventMetadataSynchronizer;

    /**
     * @param AdobeIoEventMetadataSynchronizer $eventMetadataSynchronizer
     * @param string|null $name
     */
    public function __construct(
        AdobeIoEventMetadataSynchronizer $eventMetadataSynchronizer,
        string                           $name = null
    ) {
        $this->eventMetadataSynchronizer = $eventMetadataSynchronizer;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Creates metadata in Adobe I/O from the configuration list (XML and application configurations)'
            );

        parent::configure();
    }

    /**
     * Creates events metadata in Adobe I/O from the XML and application configurations.
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
            $messages = $this->eventMetadataSynchronizer->run();
            if (count($messages)) {
                $output->writeln('The events metadata was successfully created:');
                foreach ($messages as $message) {
                    $output->writeln('   - ' . $message);
                }
            } else {
                $output->writeln('Nothing to update.');
            }

            return Cli::RETURN_SUCCESS;
        } catch (Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }
    }
}
