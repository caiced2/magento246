<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Cron;

/**
 * Custom Configurable Cron Jobs
 */
class CustomConfigurableCronJobsSection extends AbstractCronJobsSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $cronJobs = $this->cronJobs->getCronJobsByType(
            $this->cronJobs->getAllConfigurableCronJobs(),
            CronJobs::TYPE_CUSTOM
        );
        $data = $this->prepareCronList($cronJobs);

        return $this->getReportData(__('Custom Configurable Cron Jobs'), $data);
    }
}
