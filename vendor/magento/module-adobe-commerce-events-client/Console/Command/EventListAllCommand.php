<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Console\Command;

use Exception;
use Magento\AdobeCommerceEventsClient\Event\Collector\CollectorInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Module\Dir;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Collects list of events for the provided module
 */
class EventListAllCommand extends Command
{
    public const NAME = 'events:list:all';

    private const ARGUMENT_MODULE_NAME = 'module_name';

    /**
     * @var CollectorInterface
     */
    private CollectorInterface $eventCollector;

    /**
     * @var Dir
     */
    private Dir $dir;

    /**
     * @param CollectorInterface $eventCollector
     * @param Dir $dir
     * @param string|null $name
     */
    public function __construct(
        CollectorInterface $eventCollector,
        Dir $dir,
        string $name = null
    ) {
        parent::__construct($name);
        $this->eventCollector = $eventCollector;
        $this->dir = $dir;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Returns a list of subscribable events defined in the specified module')
            ->addArgument(
                self::ARGUMENT_MODULE_NAME,
                InputArgument::REQUIRED,
                'Module name'
            );

        parent::configure();
    }

    /**
     * Collects and returns the list of events for the provided module.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $modulePath = $this->dir->getDir($input->getArgument(self::ARGUMENT_MODULE_NAME));
            $events = $this->eventCollector->collect($modulePath);
            ksort($events);
            foreach ($events as $eventData) {
                $output->writeln($eventData->getEventName());
            }

            return Cli::RETURN_SUCCESS;
        } catch (Exception $e) {
            $output->writeln($e->getMessage());

            return Cli::RETURN_FAILURE;
        }
    }
}
