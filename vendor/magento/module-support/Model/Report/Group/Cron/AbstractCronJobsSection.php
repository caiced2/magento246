<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Cron;

use \Magento\Support\Model\Report\Group\Cron\CronJobs;

/**
 * Abstract class for cron group
 */
abstract class AbstractCronJobsSection extends \Magento\Support\Model\Report\Group\AbstractSection
{
    /**
     * @var \Magento\Support\Model\Report\Group\Cron\CronJobs
     */
    protected $cronJobs;

    /**
     * @param \Magento\Support\Model\Report\Group\Cron\CronJobs $cronJobs
     */
    public function __construct(
        \Magento\Support\Model\Report\Group\Cron\CronJobs $cronJobs
    ) {
        $this->cronJobs = $cronJobs;
    }

    /**
     * Get array of report data
     *
     * @param \Magento\Framework\Phrase $phrase
     * @param array $data
     * @return array
     */
    protected function getReportData(\Magento\Framework\Phrase $phrase, $data = [])
    {
        return [
            (string)$phrase => [
                'headers' => [
                    __('Job Code'), __('Cron Expression'), __('Run Class'), __('Run Method'), __('Group Code')
                ],
                'data' => $data
            ]
        ];
    }

    /**
     * Prepare report
     *
     * @param array $cronJobs
     * @return array
     */
    protected function prepareCronList($cronJobs = [])
    {
        $data = [];

        foreach ($cronJobs as $cron) {
            $data[] = $this->cronJobs->getCronInformation($cron);
        }

        return $data;
    }
}
