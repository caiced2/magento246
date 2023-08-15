<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command for collecting paths of required utilities
 */
class UtilityPathsCommand extends AbstractBackupCommand
{
    /**
     * Name of input argument
     */
    const INPUT_KEY_FORCE = 'force';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('support:utility:paths')
            ->setDescription('Create utilities paths list')
            ->setDefinition([
                new InputOption(self::INPUT_KEY_FORCE, 'f', InputOption::VALUE_NONE, 'Force'),
            ]);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->shellHelper->setRootWorkingDirectory();
            $force = (bool) $input->getOption(self::INPUT_KEY_FORCE);

            $pathsFile = $this->shellHelper->getPathsFileName();
            if (!$force && file_exists($pathsFile)) {
                throw new \Magento\Framework\Exception\AlreadyExistsException(__('Paths file already exists'));
            }

            $this->shellHelper->initPaths($force);

            $result = sprintf('<?php return %s; ?>', var_export($this->shellHelper->getUtilities(), true));

            if (file_put_contents($pathsFile, $result)) {
                $output->writeln('Paths file was created successfully!');
            } else {
                throw new \Magento\Framework\Exception\CouldNotSaveException(__('Paths file cannot be created'));
            }
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }
}
