<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Config;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Config\ConfigOptionsListConstants;

/**
 * Foreign key config Reader
 *
 * @deprecated split database solution is deprecated and will be removed
 */
class Reader extends \Magento\Framework\Config\Reader\Filesystem
{
    /**
     * List of id attributes for merge
     *
     * @var array
     */
    protected $_idAttributes = [
        '/config/entity' => 'name',
        '/config/entity/constraint' => 'name',
        '/config/entity/constraint/field' => 'name',
    ];

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resources;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var \Magento\Framework\ForeignKey\Config\Processor
     */
    protected $processor;

    /**
     * @var DbReader
     */
    protected $databaseReader;

    /**
     * @param \Magento\Framework\Config\FileResolverInterface $fileResolver
     * @param Converter $converter
     * @param SchemaLocator $schemaLocator
     * @param \Magento\Framework\Config\ValidationStateInterface $validationState
     * @param \Magento\Framework\App\ResourceConnection $resources
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param Processor $processor
     * @param DbReader $databaseReader
     * @param string $fileName
     * @param array $idAttributes
     * @param string $domDocumentClass
     * @param string $defaultScope
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Config\FileResolverInterface $fileResolver,
        \Magento\Framework\ForeignKey\Config\Converter $converter,
        SchemaLocator $schemaLocator,
        \Magento\Framework\Config\ValidationStateInterface $validationState,
        \Magento\Framework\App\ResourceConnection $resources,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\ForeignKey\Config\Processor $processor,
        DbReader $databaseReader,
        $fileName = 'constraints.xml',
        $idAttributes = [],
        $domDocumentClass = \Magento\Framework\Config\Dom::class,
        $defaultScope = 'global'
    ) {
        $this->resources = $resources;
        $this->deploymentConfig = $deploymentConfig;
        $this->processor = $processor;
        $this->databaseReader = $databaseReader;
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );
    }

    /**
     * Load configuration scope
     *
     * @param string|null $scope
     * @return array
     * @throws InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function read($scope = null)
    {
        $tablePrefixLength = strlen(
            $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX, '')
        );
        $connections = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS);
        $databaseTables = [];
        foreach (array_keys($connections) as $connectionName) {
            $connection = $this->resources->getConnectionByName($connectionName);
            foreach ($connection->getTables() as $tableName) {
                $originalTableName = substr($tableName, $tablePrefixLength);
                $databaseTables[$originalTableName] = [
                    'name' => $originalTableName,
                    'prefixed_name' => $tableName,
                    'connection' => $connectionName,
                ];
            }
        }
        $xmlConstraints = parent::read($scope);
        $databaseConstraints = $this->databaseReader->read();
        return $this->processor->process($xmlConstraints, $databaseConstraints, $databaseTables);
    }
}
