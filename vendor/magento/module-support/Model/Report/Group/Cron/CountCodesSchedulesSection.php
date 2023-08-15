<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Cron;

/**
 *  Cron Schedules by job code
 */
class CountCodesSchedulesSection extends AbstractSchedulesSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $data = $this->getCountCodesSchedules();

        return [
            (string)__('Cron Schedules by job code') => [
                'headers' => [
                    __('Job Code'), __('Count')
                ],
                'data' => $data
            ]
        ];
    }

    /**
     * Get count codes of schedules
     *
     * @return array
     */
    protected function getCountCodesSchedules()
    {
        $data = [];

        try {
            /** @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource */
            $resource = $this->scheduleCollectionFactory->create()->getResource();
            $connection = $resource->getConnection();

            $sql = "SELECT COUNT( * ) AS `cnt`, `job_code`
                FROM `{$resource->getTable('cron_schedule')}`
                GROUP BY `job_code`
                ORDER BY `job_code`";

            $countStatuses = $connection->fetchAll($sql);
            foreach ($countStatuses as $status) {
                $data[] = [$status['job_code'], $status['cnt']];
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $data;
    }
}
