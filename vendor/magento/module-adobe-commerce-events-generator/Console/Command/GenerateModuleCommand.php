<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Console\Command;

use Exception;
use Magento\AdobeCommerceEventsGenerator\Console\Command\GenerateModule\Generator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for generating a module based on a list of subscribed events
 */
class GenerateModuleCommand extends Command
{
    public const NAME = 'events:generate:module';

    /**
     * @var Generator
     */
    private Generator $generator;

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @param Generator $generator
     * @param DirectoryList $directoryList
     * @param string|null $name
     */
    public function __construct(
        Generator $generator,
        DirectoryList $directoryList,
        string $name = null
    ) {
        $this->generator = $generator;
        $this->directoryList = $directoryList;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Generate module based on plugins list');

        parent::configure();
    }

    /**
     * Runs the module generating.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $appCodeDirectory = $this->directoryList->getPath(DirectoryList::APP) . '/code';

            $this->generator->run($appCodeDirectory);

            $output->writeln('Module was generated in the app/code/Magento directory');

            return Cli::RETURN_SUCCESS;
        } catch (Exception $e) {
            $output->writeln($e->getMessage());

            return Cli::RETURN_FAILURE;
        }
    }
}
