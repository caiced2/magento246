<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Config;

use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Config\ConfigOptionsListConstants;

/**
 * Foreign key data reader
 *
 * @deprecated split database solution is deprecated and will be removed
 */
class DbReader implements ReaderInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resources;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resources
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resources,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig
    ) {
        $this->resources = $resources;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Load constraint configuration from all related databases
     *
     * @param string|null $scope
     * @return array
     * @throws InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function read($scope = null)
    {
        $connections = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS);
        $constraints = [];
        foreach (array_keys($connections) as $connectionName) {
            $constraints[] = $this->getDbConstraints($connectionName);
        }

        return array_merge(...$constraints);
    }

    /**
     * Retrieve constraints that are declared in given database
     *
     * @param string $connectionName
     * @return array
     */
    private function getDbConstraints(string $connectionName): array
    {
        $connection = $this->resources->getConnectionByName($connectionName);

        $constraints = [];

        foreach ($connection->getTables() as $tableName) {
            $foreignKeys = $connection->getForeignKeys($tableName);

            foreach ($foreignKeys as $foreignKey) {
                $row = [
                    'name'       => $foreignKey['FK_NAME'],
                    'table_name' => $foreignKey['TABLE_NAME'],
                    'reference_table_name' => $foreignKey['REF_TABLE_NAME'],
                    'field_name'           => $foreignKey['COLUMN_NAME'],
                    'reference_field_name' => $foreignKey['REF_COLUMN_NAME'],
                    'delete_strategy'      => $foreignKey['ON_DELETE'],
                ];
                $row['connection'] = $connectionName;
                $row['reference_connection'] = $connectionName;
                $row['delete_strategy'] = 'DB ' . $row['delete_strategy'];
                $key = $row['table_name']
                    . $row['reference_table_name']
                    . $row['field_name']
                    . $row['reference_field_name'];
                $constraintId = sha1($key);
                $constraints[$constraintId] = $row;
            }
        }

        return $constraints;
    }
}
