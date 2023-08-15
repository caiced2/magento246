<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @var $resourceModel Magento\CatalogRule\Model\ResourceModel\Rule
 */
$resourceModel = Bootstrap::getObjectManager()->create(\Magento\Staging\Model\ResourceModel\Update::class);
$entityIdField = $resourceModel->getIdFieldName();
$entityTable = $resourceModel->getMainTable();

/**
 * @var $resource Magento\Framework\App\ResourceConnection
 */
$resource = Bootstrap::getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection();

$updates = [
    [
        $entityIdField => 1,
        'rollback_id' => null,
        'is_rollback' => null,
        'name' => 'Permanent update 1',
        'start_time' => null
    ],
    [
        $entityIdField => 100,
        'rollback_id' => null,
        'is_rollback' => null,
        'name' => 'Permanent update 100',
        'start_time' => null
    ],
    [
        $entityIdField => 111,
        'rollback_id' => null,
        'is_rollback' => null,
        'name' => 'Permanent update 11',
        'start_time' => date('Y-m-d H:i:s', strtotime('+1 day'))
    ],
    [
        $entityIdField => 112,
        'rollback_id' => null,
        'is_rollback' => null,
        'name' => 'Permanent update 101',
        'start_time' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ],
];

foreach ($updates as $update) {
    $connection->query(
        "INSERT INTO {$entityTable} (`{$entityIdField}`, `rollback_id`, `is_rollback`, `name`, `start_time`)"
        . " VALUES (:{$entityIdField}, :rollback_id, :is_rollback, :name, :start_time);",
        $update
    );
}
