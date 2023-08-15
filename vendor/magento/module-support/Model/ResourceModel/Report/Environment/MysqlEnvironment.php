<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\ResourceModel\Report\Environment;

/**
 * Class MysqlEnvironment
 */
class MysqlEnvironment extends AbstractEnvironment
{
    /**#@+
     * Labels for report
     */
    const DB_VERSION = 'MySQL Server Version';
    const DB_ENGINES = 'MySQL Supported Engines';
    const DB_AMOUNT = 'MySQL Databases Present';
    const DB_CONFIGURATION = 'MySQL Configuration';
    const DB_PLUGINS = 'MySQL Plugins';
    /**#@-*/

    /**#@-*/
    protected $dataFormatter;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param PhpInfo $phpInfo
     * @param \Magento\Framework\Module\ModuleResource $resource
     * @param \Magento\Support\Model\DataFormatter $dataFormatter
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        PhpInfo $phpInfo,
        \Magento\Framework\Module\ModuleResource $resource,
        \Magento\Support\Model\DataFormatter $dataFormatter
    ) {
        parent::__construct($logger, $phpInfo, $resource);
        $this->dataFormatter = $dataFormatter;
    }

    /**
     * Get version of MySQL server
     *
     * @return array
     */
    public function getVersion()
    {
        if (method_exists($this->resourceConnection, 'getServerVersion')) {
            $data = [self::DB_VERSION, $this->resourceConnection->getServerVersion()];
        } else {
            $data = [self::DB_VERSION, 'n/a'];
        }

        return $data;
    }

    /**
     * Get supported engines of DB
     *
     * @return array
     */
    public function getSupportedEngines()
    {
        try {
            $engines = $this->resourceConnection->fetchAll('SHOW ENGINES');
            $supportedEngines = '';

            if ($engines) {
                foreach ($engines as $engine) {
                    if ($engine['Support'] != 'NO' && $engine['Engine'] != 'DISABLED') {
                        $supportedEngines .= $engine['Engine'] . '; ';
                    }
                }
            } else {
                $supportedEngines = 'n/a';
            }
            $data = [self::DB_ENGINES, $supportedEngines];
            unset($engines, $supportedEngines);
        } catch (\Exception $e) {
            $this->logger->error($e);
            $data = [self::DB_ENGINES, 'n/a'];
        }

        return $data;
    }

    /**
     * Get amount databases
     *
     * @return array
     */
    public function getDbAmount()
    {
        try {
            $databases = $this->resourceConnection->fetchAll('SHOW DATABASES');
            $dbNumber = $databases ? sizeof($databases) : 0;
            $data = [self::DB_AMOUNT, $dbNumber];
            unset($databases);
        } catch (\Exception $e) {
            $this->logger->error($e);
            $data = [self::DB_AMOUNT, 'n/a'];
        }

        return $data;
    }

    /**
     * Get configuration of DB
     *
     * @return array
     */
    public function getDbConfiguration()
    {
        $importantConfig = [
            'datadir',
            'default_storage_engine',
            'general_log',
            'general_log_file',
            'innodb_buffer_pool_size',
            'innodb_io_capacity',
            'innodb_log_file_size',
            'innodb_thread_concurrency',
            'innodb_flush_log_at_trx_commit',
            'innodb_open_files',
            'join_buffer_size',
            'key_buffer_size',
            'max_allowed_packet',
            'max_connect_errors',
            'max_connections',
            'max_heap_table_size',
            'query_cache_size',
            'query_cache_limit',
            'read_buffer_size',
            'skip_name_resolve',
            'slow_query_log',
            'slow_query_log_file',
            'sync_binlog',
            'table_open_cache',
            'tmp_table_size',
            'wait_timeout',
            'version'
        ];

        try {
            $variables = $this->resourceConnection->fetchAssoc('SHOW GLOBAL VARIABLES');
            if ($variables) {
                $configuration = '';
                foreach ($variables as $variable) {
                    if (!in_array($variable['Variable_name'], $importantConfig)) {
                        continue;
                    }
                    if (substr($variable['Variable_name'], -4) == 'size') {
                        $variable['Value'] = $this->dataFormatter->formatBytes($variable['Value'], 3, 'IEC');
                    }
                    $configuration .= $variable['Variable_name'] . ' => "' . $variable['Value'] . '"' . "\n";
                }
                $configuration = trim($configuration);
            } else {
                $configuration = 'n/a';
            }
            $data = [self::DB_CONFIGURATION, trim($configuration)];
            unset($variables);
        } catch (\Exception $e) {
            $this->logger->error($e);
            $data = [self::DB_CONFIGURATION, 'n/a'];
        }

        return $data;
    }

    /**
     * Get plugins list of DB
     *
     * @return array
     */
    public function getPlugins()
    {
        try {
            $plugins = $this->resourceConnection->fetchAssoc('SHOW PLUGINS');
            $installedPlugins = '';
            if ($plugins) {
                foreach ($plugins as $plugin) {
                    $installedPlugins .= ($plugin['Status'] == 'DISABLED' ? '-disabled- ' : '')
                        . $plugin['Name'] . "\n";
                }
            } else {
                $installedPlugins = 'n/a';
            }
            $data = [self::DB_PLUGINS, trim($installedPlugins)];
            unset($plugins, $installedPlugins);
        } catch (\Exception $e) {
            $this->logger->error($e);
            $data = [self::DB_PLUGINS, 'n/a'];
        }

        return $data;
    }
}
