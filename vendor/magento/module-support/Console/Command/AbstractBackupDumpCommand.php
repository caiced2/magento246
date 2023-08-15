<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Support\Helper\Shell as ShellHelper;
use Magento\Support\Model\Backup\Config as BackupConfig;

/**
 * An Abstract class for all backup related commands.
 */
class AbstractBackupDumpCommand extends AbstractBackupCommand
{
    /**#@+
     * Names of input arguments or options
     */
    const INPUT_KEY_NAME   = 'name';
    const INPUT_KEY_OUTPUT = 'output';
    const INPUT_KEY_LOGS   = 'logs';
    /**#@- */

    /**#@- */
    protected $backupName;

    /**
     * @var string
     */
    protected $outputPath;

    /**
     * @var DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var array
     */
    protected $dbParams = [];

    /**
     * @var string
     */
    protected $dbConnectionParams;

    /**
     * @var array
     */
    protected $connectionOptions = [];

    /**
     * @param ShellHelper $shellHelper
     * @param BackupConfig $backupConfig
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        ShellHelper $shellHelper,
        BackupConfig $backupConfig,
        DeploymentConfig $deploymentConfig
    ) {
        parent::__construct($shellHelper, $backupConfig);
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Get list of options and arguments for the command
     *
     * @return array
     */
    public function getInputList()
    {
        return [
            new InputOption(self::INPUT_KEY_NAME, null, InputOption::VALUE_OPTIONAL, 'Dump name'),
            new InputOption(self::INPUT_KEY_OUTPUT, 'o', InputOption::VALUE_OPTIONAL, 'Output path'),
            new InputOption(self::INPUT_KEY_LOGS, 'l', InputOption::VALUE_NONE, 'Include logs')
        ];
    }

    /**
     * Get name of backup file
     *
     * @param InputInterface $input
     * @return mixed|string
     */
    protected function getBackupName(InputInterface $input)
    {
        if (!$this->backupName) {
            $name = $input->getOption(self::INPUT_KEY_NAME);
            if (!$name) {
                $name = hash("sha256", gmdate('r') . random_int(0, 32767)) . '.' . date('Ymdhi');
            }
            $this->backupName = $name;
        }

        return $this->backupName;
    }

    /**
     * Get output path for backup
     *
     * @param InputInterface $input
     * @return mixed|string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    protected function getOutputPath(InputInterface $input)
    {
        if (!$this->outputPath) {
            $outputPath = $input->getOption(self::INPUT_KEY_OUTPUT);
            if (!$outputPath) {
                $outputPath = $this->shellHelper->getAbsoluteOutputPath();
            }

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (!is_dir($outputPath)) {
                throw new \Magento\Framework\Exception\NotFoundException(
                    __('Output path %1 doesn\'t exist', $outputPath)
                );
            }
            $this->outputPath = $outputPath;
        }

        return $this->outputPath;
    }

    /**
     * Get DB tables prefix
     *
     * @return array|null
     */
    protected function getPrefix()
    {
        return $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);
    }

    /**
     * Get DB connection parameter
     *
     * @param string $name
     * @return mixed
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    protected function getParam($name)
    {
        if (!$this->dbParams) {
            $this->dbParams = $this->deploymentConfig->get(
                ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT
            );
        }

        if (!isset($this->dbParams[$name])) {
            throw new \Magento\Framework\Exception\NotFoundException(__('Unknown DB param: %1', $name));
        }

        return $this->dbParams[$name];
    }

    /**
     * Get connection params for dump query
     *
     * @return string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    protected function getConnectionParams()
    {
        if (!$this->dbConnectionParams) {
            $password = $this->getParam(ConfigOptionsListConstants::KEY_PASSWORD);
            if (!empty($password)) {
                $password = sprintf('-p"%s"', $password);
            }

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $urlParams = parse_url($this->getParam(ConfigOptionsListConstants::KEY_HOST));
            $port = isset($urlParams[ConfigOptionsListConstants::KEY_PORT])
                ? sprintf('--port %s', $urlParams[ConfigOptionsListConstants::KEY_PORT])
                : '';
            $host = isset($urlParams[ConfigOptionsListConstants::KEY_HOST])
                ? $urlParams[ConfigOptionsListConstants::KEY_HOST]
                : $this->getParam(ConfigOptionsListConstants::KEY_HOST);

            $this->dbConnectionParams = sprintf(
                '-u%s -h%s %s %s %s %s',
                $this->getParam(ConfigOptionsListConstants::KEY_USER),
                $host,
                $port,
                $password,
                $this->getParam(ConfigOptionsListConstants::KEY_NAME),
                implode(' ', $this->connectionOptions)
            );
        }

        return $this->dbConnectionParams;
    }
}
