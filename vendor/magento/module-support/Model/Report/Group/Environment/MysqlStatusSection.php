<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Environment;

/**
 * Mysql status report
 */
class MysqlStatusSection extends \Magento\Support\Model\Report\Group\AbstractSection
{
    /**
     * Report title
     */
    const REPORT_TITLE = 'MySQL Status';

    /**
     * @var array
     */
    protected $importantConfig = [
        'Aborted_clients',
        'Aborted_connects',
        'Com_select',
        'Connections',
        'Created_tmp_disk_tables',
        'Created_tmp_files',
        'Created_tmp_tables',
        'Handler_read_rnd_next',
        'Innodb_buffer_pool_read_requests',
        'Innodb_buffer_pool_write_requests',
        'Innodb_log_waits',
        'Innodb_log_write_requests',
        'Innodb_log_writes',
        'Open_files',
        'Open_streams',
        'Open_table_definitions',
        'Open_tables',
        'Opened_files',
        'Opened_table_definitions',
        'Opened_tables',
        'Qcache_lowmem_prunes',
        'Select_full_join',
        'Select_full_range_join',
        'Select_range',
        'Select_range_check',
        'Select_scan',
        'Slow_queries',
        'Slave_running',
        'Sort_range',
        'Sort_rows',
        'Sort_scan',
        'Table_locks_immediate',
        'Table_locks_waited',
        'Threads_cached',
        'Threads_connected',
        'Threads_created',
        'Threads_running'
    ];

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $resourceConnection;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Module\ModuleResource $resource
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Module\ModuleResource $resource,
        array $data = []
    ) {
        $this->resourceConnection = $resource->getConnection();
        parent::__construct($logger, $data);
    }

    /**
     * Generate MySQL Status information
     *
     * @return array
     */
    public function generate()
    {
        $data = [];

        $variables = $this->resourceConnection->fetchPairs('SHOW GLOBAL STATUS');
        sleep(10);
        $variablesAfter10Sec = $this->resourceConnection->fetchPairs('SHOW GLOBAL STATUS');

        if ($variables && $variablesAfter10Sec) {
            foreach ($variables as $name => $value) {
                if (!in_array($name, $this->importantConfig)) {
                    continue;
                }
                $valueAfter10Sec = 'n/a';
                if (isset($variablesAfter10Sec[$name])) {
                    $difference = '';
                    if (is_numeric($variablesAfter10Sec[$name])) {
                        $difference = $variablesAfter10Sec[$name] - $value;
                        if ($difference != 0) {
                            $difference = ' (diff: ' . ($difference > 0 ? '+' : '') . $difference . ')';
                        } else {
                            $difference = '';
                        }
                    }
                    $valueAfter10Sec = $variablesAfter10Sec[$name] . $difference;
                }
                $data[] = [$name, $value, $valueAfter10Sec];
            }
        }
        unset($variables, $variablesAfter10Sec);

        return [
            self::REPORT_TITLE => [
                'headers' => ['Variable', 'Value', 'Value after 10 sec'],
                'data' => $data
            ]
        ];
    }
}
