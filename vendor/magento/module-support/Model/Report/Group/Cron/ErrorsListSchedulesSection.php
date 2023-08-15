<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Cron;

/**
 * Errors in Cron Schedule Queue
 */
class ErrorsListSchedulesSection extends AbstractSchedulesSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $data = $this->getErrorsSchedulesList();
        return [
            (string)__('Errors in Cron Schedules Queue') => [
                'headers' => [
                    __('Schedule Id'), __('Job Code'), __('Error'), __('Count'),
                    __('Created At'), __('Scheduled At'), __('Executed At'), __('Finished At')
                ],
                'data' => $data
            ]
        ];
    }

    /**
     * Get errors list
     *
     * @return array
     */
    protected function getErrorsSchedulesList()
    {
        $data = [];
        $cronSchedules = [];

        try {
            $collection = $this->scheduleCollectionFactory->create();
            $collection->addFieldToFilter('status', \Magento\Cron\Model\Schedule::STATUS_ERROR);
            $cronSchedules = $collection->load();
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        $jobs = [];
        /** @var \Magento\Cron\Model\Schedule $schedule */
        foreach ($cronSchedules as $schedule) {
            try {
                $jobData = [
                    'id' => $schedule->getId(),
                    'job_code' => $schedule->getJobCode(),
                    'message' => $schedule->getMessages(),
                    'created_at' => $schedule->getCreatedAt(),
                    'schedule_at' => $schedule->getScheduledAt(),
                    'execute_at' => $schedule->getExecutedAt(),
                    'finish_at' => $schedule->getFinishedAt()
                ];

                if (empty($jobs[$jobData['job_code']][$jobData['message']])) {
                    $jobs[$jobData['job_code']][$jobData['message']]['cnt'] = 1;
                } else {
                    $jobs[$jobData['job_code']][$jobData['message']]['cnt']++;
                }

                $jobs[$jobData['job_code']][$jobData['message']]['data'] = $jobData;
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }

        foreach ($jobs as $messages) {
            foreach ($messages as $message) {
                $data[] = [
                    $message['data']['id'],
                    $message['data']['job_code'],
                    $message['data']['message'],
                    $message['cnt'],
                    $message['data']['created_at'],
                    $message['data']['schedule_at'],
                    $message['data']['execute_at'],
                    $message['data']['finish_at'],
                ];
            }
        }

        return $data;
    }
}
