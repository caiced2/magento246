<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Data;

/**
 * Report Duplicate Orders By Increment Id
 */
class DuplicateOrdersByIncrementIdSection extends AbstractDataGroup
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        return [
            (string)__('Duplicate Orders By Increment Id') => [
                'headers' => [__('Id'), __('Increment Id'), __('Store'), __('Created At'), __('Customer Id')],
                'data' => $this->getDuplicateOrders()
            ]
        ];
    }

    /**
     * Get duplicate orders by increment id
     *
     * @return array
     */
    protected function getDuplicateOrders()
    {
        $data = [];

        try {
            $entityTable = $this->resource->getTable('sales_order');
            $storeTable = $this->resource->getTable('store');

            $sqlGetDuplicates = 'SELECT COUNT(1) AS `cnt`, `increment_id`'
                . ' FROM `' . $entityTable . '`'
                . ' GROUP BY `increment_id`'
                . ' HAVING `cnt` > 1'
                . ' ORDER BY `cnt` DESC, `entity_id`';
            $duplicateList = $this->connection->fetchAll($sqlGetDuplicates);

            foreach ($duplicateList as $duplicate) {
                $sql = 'SELECT `e`.`entity_id`, `e`.`store_id`, `e`.`customer_id`,'
                    . ' `e`.`increment_id`, `e`.`created_at`, `s`.`name` AS `store_name`'
                    . ' FROM `' . $entityTable . '` AS `e`'
                    . ' LEFT JOIN `' . $storeTable .'` AS `s` USING(store_id)'
                    . ' WHERE ' . $this->connection->quoteInto('`e`.`increment_id` = ?', $duplicate['increment_id']);
                $entities = $this->connection->fetchAll($sql);

                foreach ($entities as $entity) {
                    $data[] = [
                        $entity['entity_id'],
                        $duplicate['increment_id'],
                        $entity['store_name'] . ' {ID:' . $entity['store_id'] . '}',
                        $entity['created_at'],
                        $entity['customer_id'],
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $data;
    }
}
