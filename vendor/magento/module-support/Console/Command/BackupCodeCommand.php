<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Console\Command;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Filesystem;
use Magento\Support\Helper\Shell as ShellHelper;
use Magento\Support\Model\Backup\Config as BackupConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for displaying current index mode for indexers.
 */
class BackupCodeCommand extends AbstractBackupDumpCommand
{
    /**
     * @var array
     */
    protected $backupList = [
        'app',
        'bin',
        'composer.*',
        'dev',
        '*.php',
        'lib',
        'pub/*.php',
        'pub/errors',
        'setup',
        'vendor'
    ];

    /**
     * @var array
     */
    protected $backupLogsList = [
        'var/log',
        'var/report'
    ];

    /**
     * @var Filesystem
     * @deprecated 100.1.0
     */
    protected $filesystem;

    /**
     * BackupCodeCommand constructor
     *
     * @param ShellHelper $shellHelper
     * @param BackupConfig $backupConfig
     * @param DeploymentConfig $deploymentConfig
     * @param Filesystem $filesystem
     * @deprecated 100.1.0
     */
    public function __construct(
        ShellHelper $shellHelper,
        BackupConfig $backupConfig,
        DeploymentConfig $deploymentConfig,
        Filesystem $filesystem
    ) {
        parent::__construct($shellHelper, $backupConfig, $deploymentConfig);
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('support:backup:code')
            ->setDescription('Create Code backup')
            ->setDefinition($this->getInputList());
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->shellHelper->setRootWorkingDirectory();

            $filePath    = $this->getOutputPath($input) . DIRECTORY_SEPARATOR . $this->getBackupName($input);
            $includeLogs = (bool) $input->getOption(self::INPUT_KEY_LOGS);

            $backupCodeCommand = $this->getBackupCodeCommand($filePath);
            $output->writeln($backupCodeCommand);
            $output->writeln($this->shellHelper->execute($backupCodeCommand));

            if ($includeLogs) {
                $backupLogsCommand = $this->getBackupLogsCommand($filePath);
                $output->writeln($backupLogsCommand);
                $output->writeln($this->shellHelper->execute($backupLogsCommand));
            }

            $output->writeln('Code dump was created successfully');
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());
            $previousException = $e->getPrevious();
            if ($previousException instanceof \Exception) {
                $output->writeln('More information:');
                $output->writeln($previousException->getMessage());
            }
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }

    /**
     * Get console command for code backup
     *
     * @param string $filePath
     * @return string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    protected function getBackupCodeCommand($filePath)
    {
        $fileExtension = $this->backupConfig->getBackupFileExtension('code');

        $command = sprintf(
            '%s -n 15 %s -czhf %s %s',
            $this->shellHelper->getUtility(ShellHelper::UTILITY_NICE),
            $this->shellHelper->getUtility(ShellHelper::UTILITY_TAR),
            $filePath . '.' . ($fileExtension ?: 'tar.gz'),
            implode(' ', $this->getBackupList())
        );

        return $command;
    }

    /**
     * Get console command for logs backup
     *
     * @param string $filePath
     * @return string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    protected function getBackupLogsCommand($filePath)
    {
        $command = sprintf(
            '%s -n 15 %s -czhf %s %s',
            $this->shellHelper->getUtility(ShellHelper::UTILITY_NICE),
            $this->shellHelper->getUtility(ShellHelper::UTILITY_TAR),
            $filePath . '.logs.tar.gz',
            implode(' ', $this->backupLogsList)
        );

        return $command;
    }

    /**
     * Search if file exists
     *
     * @return array
     */
    private function getBackupList(): array
    {
        return array_filter($this->backupList, function ($pathPattern) {
            return $this->shellHelper->pathExists($pathPattern);
        });
    }
}
