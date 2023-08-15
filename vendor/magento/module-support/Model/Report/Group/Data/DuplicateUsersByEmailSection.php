<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Data;

/**
 * Report Duplicate Users By Email
 */
class DuplicateUsersByEmailSection extends AbstractDataGroup
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        return [
            (string)__('Duplicate Users By Email') => [
                'headers' => [__('Id'), __('Email'), __('Website'), __('Created At')],
                'data' => $this->getDuplicateUsers()
            ]
        ];
    }

    /**
     * Get duplicate users
     *
     * @return array
     */
    protected function getDuplicateUsers()
    {
        $data = [];

        try {
            $entityTable = $this->resource->getTable('customer_entity');
            $storeWebsiteTable = $this->resource->getTable('store_website');

            $sqlGetDuplicates = 'SELECT COUNT(1) AS `cnt`, `email`'
                . ' FROM `' . $entityTable . '`'
                . ' GROUP BY `email`'
                . ' HAVING `cnt` > 1'
                . ' ORDER BY `cnt` DESC, `entity_id`';
            $duplicateList = $this->connection->fetchAll($sqlGetDuplicates);

            foreach ($duplicateList as $duplicate) {
                $sql = 'SELECT `e`.`entity_id`, `e`.`email`, `e`.`website_id`, `e`.`created_at`,'
                    . ' `w`.`name` as `website_name`'
                    . ' FROM `' . $entityTable . '` AS `e`'
                    . ' LEFT JOIN `' . $storeWebsiteTable . '` AS `w` USING(website_id)'
                    . ' WHERE ' . $this->connection->quoteInto('`e`.`email` = ?', $duplicate['email']);
                $entities = $this->connection->fetchAll($sql);

                foreach ($entities as $entity) {
                    $data[] = [
                        $entity['entity_id'],
                        $duplicate['email'],
                        $entity['website_name'] . ' {ID:' . $entity['website_id'] . '}',
                        $entity['created_at'],
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $data;
    }
}
