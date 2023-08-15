<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Cron;

/**
 * Core Configurable Cron Jobs
 */
class CoreConfigurableCronJobsSection extends AbstractCronJobsSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $cronJobs = $this->cronJobs->getCronJobsByType(
            $this->cronJobs->getAllConfigurableCronJobs(),
            CronJobs::TYPE_CORE
        );
        $data = $this->prepareCronList($cronJobs);

        return $this->getReportData(__('Core Configurable Cron Jobs'), $data);
    }
}
