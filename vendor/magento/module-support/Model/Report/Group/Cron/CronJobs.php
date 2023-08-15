<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Cron;

/**
 * Class retrieves information about Cron Jobs
 */
class CronJobs
{
    /**#@+
     * Types Cron Jobs
     */
    const TYPE_CORE = 1;
    const TYPE_CUSTOM = 2;
    /**#@-*/

    /**#@-*/
    protected $cronConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array|null
     */
    protected $allCronList;

    /**
     * @var array|null
     */
    protected $configurableCronList;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Laminas\Server\Reflection
     */
    protected $reflection;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Cron\Model\ConfigInterface $cronConfig
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Laminas\Server\Reflection $reflection
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Cron\Model\ConfigInterface $cronConfig,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Laminas\Server\Reflection $reflection
    ) {
        $this->cronConfig = $cronConfig;
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
        $this->reflection = $reflection;
    }

    /**
     * Get all global cron jobs
     *
     * @return array
     */
    public function getAllCronJobs()
    {
        if ($this->allCronList === null) {
            $this->allCronList = [];
            $cronJobs = $this->cronConfig->getJobs();
            foreach ($cronJobs as $cronGroup => $jobs) {
                foreach ($jobs as $cronName => $cron) {
                    if (empty($cron['name'])) {
                        $cron['name'] = $cronName;
                    }
                    $cron['group_code'] = $cronGroup;
                    $this->allCronList[$cron['name']] = $cron;
                }
            }

            ksort($this->allCronList);
        }

        return $this->allCronList;
    }

    /**
     * Get cron jobs by type
     *
     * @param array $cronJobs
     * @param int $type
     * @return array
     */
    public function getCronJobsByType($cronJobs = [], $type = self::TYPE_CORE)
    {
        $data = [];

        foreach ($cronJobs as $cron) {
            if ((!$this->isCustomCronJob($cron['instance']) && $type === self::TYPE_CORE)
                || ($this->isCustomCronJob($cron['instance']) && $type === self::TYPE_CUSTOM)
            ) {
                $data[$cron['name']] = $cron;
            }
        }

        return $data;
    }

    /**
     * Get all configurable cron jobs
     *
     * @return array
     */
    public function getAllConfigurableCronJobs()
    {
        if ($this->configurableCronList === null) {
            $configurableList = $this->scopeConfig->getValue('crontab');

            $this->configurableCronList = [];
            $cronJobs = $this->getAllCronJobs();

            foreach ($cronJobs as $cron) {
                if (isset($configurableList[$cron['group_code']]['jobs'][$cron['name']])) {
                    $this->configurableCronList[$cron['name']] = $cron;
                }
            }
        }

        return $this->configurableCronList;
    }

    /**
     * Get cron job information
     *
     * @param array $cron
     * @return array
     */
    public function getCronInformation($cron = [])
    {
        $expression = $this->getCronExpression($cron);

        $data = [
            $cron['name'],
            $expression ?: 'n/a',
            $cron['instance']
                ? $cron['instance'] . "\n" . '{' . $this->getFilePathByNamespace($cron['instance']) . '}'
                : 'n/a',
            $cron['method'] ?: 'n/a',
            $cron['group_code']
        ];

        return $data;
    }

    /**
     * Get cron expression
     *
     * @param array $cron
     * @return string|null
     */
    public function getCronExpression($cron = [])
    {
        $expression = null;
        if (!empty($cron['config_path'])) {
            $expression = $this->scopeConfig->getValue($cron['config_path']);
        } elseif (!empty($cron['schedule'])) {
            $expression = $cron['schedule'];
        }

        return $expression;
    }

    /**
     * Check if specified instance of cron job is custom
     *
     * @param string $instance
     * @return bool
     */
    public function isCustomCronJob($instance)
    {
        $instance = trim($instance, '\\');
        return substr($instance, 0, 8) != 'Magento\\';
    }

    /**
     * Get file path of instance
     *
     * @param string $instance
     * @return string
     */
    public function getFilePathByNamespace($instance)
    {
        try {
            $root = $this->directoryList->getRoot();
            $rc = $this->reflection->reflectClass($instance);
            $filePath = $rc->getFileName();

            return substr($filePath, strlen($root) + 1);
        } catch (\Exception $e) {
            return 'n/a';
        }
    }
}
