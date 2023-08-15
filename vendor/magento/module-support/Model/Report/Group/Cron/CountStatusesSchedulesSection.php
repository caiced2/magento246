<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Cron;

/**
 * Cron Schedules by status code
 */
class CountStatusesSchedulesSection extends AbstractSchedulesSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $data = $this->getCountStatusesSchedules();

        return [
            (string)__('Cron Schedules by status code') => [
                'headers' => [
                    __('Status Code'), __('Count')
                ],
                'data' => $data
            ]
        ];
    }

    /**
     * Get count statuses of schedules
     *
     * @return array
     */
    protected function getCountStatusesSchedules()
    {
        $data = [];

        try {
            /** @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource */
            $resource = $this->scheduleCollectionFactory->create()->getResource();
            $connection = $resource->getConnection();

            $sql = "SELECT COUNT( * ) AS `cnt`, `status`
                FROM `{$resource->getTable('cron_schedule')}`
                GROUP BY `status`
                ORDER BY `status`";

            $countStatuses = $connection->fetchAll($sql);
            foreach ($countStatuses as $status) {
                $data[] = [$status['status'], $status['cnt']];
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $data;
    }
}
