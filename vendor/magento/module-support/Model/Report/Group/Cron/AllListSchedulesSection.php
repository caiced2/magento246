<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Cron;

/**
 * Cron Schedules List
 */
class AllListSchedulesSection extends AbstractSchedulesSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $data = $this->getSchedulesList();
        return [
            (string)__('Cron Schedules List') => [
                'headers' => [
                    __('Schedule Id'), __('Job Code'), __('Status'), __('Created At'),
                    __('Scheduled At'), __('Executed At'), __('Finished At')
                ],
                'data' => $data
            ]
        ];
    }

    /**
     * Get schedules list
     *
     * @return array
     */
    protected function getSchedulesList()
    {
        $data = [];
        $cronSchedules = [];

        try {
            $collection = $this->scheduleCollectionFactory->create();
            $cronSchedules = $collection->load();
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        /** @var \Magento\Cron\Model\Schedule $schedule */
        foreach ($cronSchedules as $schedule) {
            try {
                $jobData = [
                    $schedule->getId(),
                    $schedule->getJobCode(),
                    $schedule->getStatus(),
                    $schedule->getCreatedAt(),
                    $schedule->getScheduledAt(),
                    $schedule->getExecutedAt(),
                    $schedule->getFinishedAt()
                ];

                $data[] = $jobData;
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }

        return $data;
    }
}
