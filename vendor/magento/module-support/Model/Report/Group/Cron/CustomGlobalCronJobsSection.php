<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Cron;

/**
 * Custom Global Cron Jobs
 */
class CustomGlobalCronJobsSection extends AbstractCronJobsSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $cronJobs = $this->cronJobs->getCronJobsByType(
            $this->cronJobs->getAllCronJobs(),
            CronJobs::TYPE_CUSTOM
        );
        $data = $this->prepareCronList($cronJobs);

        return $this->getReportData(__('Custom Global Cron Jobs'), $data);
    }
}
