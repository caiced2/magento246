<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Support\Helper\Shell as ShellHelper;

/**
 * Command for backup Database
 */
class BackupDbCommand extends AbstractBackupDumpCommand
{
    /**
     * Name of input argument
     */
    public const INPUT_KEY_IGNORE_SANITIZE = 'ignore-sanitize';

    /**
     * @var array
     */
    protected $skippedTables = [
        'magento_logging_event',
        'magento_logging_event_changes',
        'report_event',
        'report_viewed_product_index',
        'support_backup',
        'support_backup_item',
    ];

    /**
     * @var array
     */
    protected $sanitizedTables = [
        'customer_entity',
        'customer_entity_varchar',
        'customer_address_entity',
        'customer_address_entity_varchar',
        'customer_grid_flat',
        'quote',
        'quote_address',
        'sales_order',
        'sales_order_address',
        'sales_order_grid'
    ];

    /**
     * @var array
     */
    protected $connectionOptions = [
        '--force',
        '--triggers',
        '--single-transaction',
        '--opt',
        '--skip-lock-tables'
    ];

    /**
     * @var array
     */
    protected $ignoredTables = [];

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('support:backup:db')
            ->setDescription('Create DB backup')
            ->setDefinition($this->getInputList())
            ->addOption(self::INPUT_KEY_IGNORE_SANITIZE, 'i', InputOption::VALUE_NONE, 'Ignore sanitize');
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->shellHelper->setRootWorkingDirectory();
            $command = $this->getCommand($input);
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                if ($this->getParam(ConfigOptionsListConstants::KEY_PASSWORD)) {
                    $output->writeln(str_replace(
                        '-p"' . $this->getParam(ConfigOptionsListConstants::KEY_PASSWORD) . '"',
                        '-p[******]',
                        $command
                    ));
                } else {
                    $output->writeln($command);
                }
            }

            $output->writeln($this->shellHelper->execute($command));

            $output->writeln('DB dump was created successfully');

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }

    /**
     * Get DB backup console command
     *
     * @param InputInterface $input
     * @return string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    protected function getCommand(InputInterface $input)
    {
        $filePath = $this->getOutputPath($input) . DIRECTORY_SEPARATOR . $this->getBackupName($input);
        $fileExtension = $this->backupConfig->getBackupFileExtension('db');

        return sprintf(
            '(%s %s %s) | %s -e \'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/; /^Warning: Using a password/d\' | %s > %s',
            !$input->getOption(self::INPUT_KEY_IGNORE_SANITIZE) ? $this->getSanitizeSubCommand() : '',
            !$input->getOption(self::INPUT_KEY_LOGS) ? $this->getSkipTablesSubCommand() : '',
            $this->getIgnoredTablesSubCommand(),
            $this->shellHelper->getUtility(ShellHelper::UTILITY_SED),
            $this->shellHelper->getUtility(ShellHelper::UTILITY_GZIP),
            $filePath . '.' . ($fileExtension ?: 'sql.gz')
        );
    }

    /**
     * Get console subcommand for sanitized data
     *
     * @return string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    protected function getSanitizeSubCommand()
    {
        $sanitizedTables = "";
        foreach ($this->sanitizedTables as $tableName) {
            $sanitizedTables .= sprintf(' %s%s', $this->getPrefix(), $tableName);
            $this->ignoredTables[] = $tableName;
        }

        return sprintf(
            '%s -n 15 %s %s --skip-extended-insert %s | %s -r %s;',
            $this->shellHelper->getUtility(ShellHelper::UTILITY_NICE),
            $this->shellHelper->getUtility(ShellHelper::UTILITY_MYSQLDUMP),
            $this->getConnectionParams(),
            $sanitizedTables,
            $this->shellHelper->getUtility(ShellHelper::UTILITY_PHP),
            //phpcs:ignore Magento2.Functions.DiscouragedFunction
            escapeshellarg($this->getSanitizeCode())
        );
    }

    /**
     * Get console subcommand for skipped tables
     *
     * @return string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    protected function getSkipTablesSubCommand()
    {
        $skippedTables = "";
        foreach ($this->skippedTables as $tableName) {
            $skippedTables .= sprintf(' %s%s', $this->getPrefix(), $tableName);
            $this->ignoredTables[] = $tableName;
        }

        return sprintf(
            '%s -n 15 %s --no-data %s %s 2>/dev/null;',
            $this->shellHelper->getUtility(ShellHelper::UTILITY_NICE),
            $this->shellHelper->getUtility(ShellHelper::UTILITY_MYSQLDUMP),
            $this->getConnectionParams(),
            $skippedTables
        );
    }

    /**
     * Get console subcommand for ignored tables
     *
     * @return string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    protected function getIgnoredTablesSubCommand()
    {
        $ignoredTables = "";
        foreach ($this->ignoredTables as $tableName) {
            $ignoredTables .= sprintf(
                ' --ignore-table=\'%s\'.\'%s%s\'',
                $this->getParam(ConfigOptionsListConstants::KEY_NAME),
                $this->getPrefix(),
                $tableName
            );
        }

        return sprintf(
            '%s -n 15 %s %s %s;',
            $this->shellHelper->getUtility(ShellHelper::UTILITY_NICE),
            $this->shellHelper->getUtility(ShellHelper::UTILITY_MYSQLDUMP),
            $this->getConnectionParams(),
            $ignoredTables
        );
    }

    /**
     * Get PHP code for sanitizing SQL queries
     *
     * @return string
     */
    protected function getSanitizeCode()
    {
        $code = <<<'PHP_CODE'
while ($line=fgets(STDIN)) {
    if (preg_match("/(^INSERT INTO\s+\S+\s+VALUES\s+)\((.*)\);$/",$line,$matches)) {
        $row = str_getcsv($matches[2],",","\x27");
        foreach($row as $key => $field) {
            if ($field == "NULL") {
                continue;
            } elseif (preg_match("/[A-Z]/i", $field)) {
                $field = md5($field . rand());
            } elseif (preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $field)) {
                $field = date("Y-m-d H:i:s");
            } elseif (preg_match("/\d{4}-\d{2}-\d{2}/", $field)) {
                $field = date("Y-m-d");
            }
            $row[$key] = "\x27" . $field . "\x27";
        }
        echo $matches[1] . "(" . implode(",", $row) . ");\n";
        continue;
    }
    echo $line;
};
PHP_CODE;
        return trim($code);
    }
}
