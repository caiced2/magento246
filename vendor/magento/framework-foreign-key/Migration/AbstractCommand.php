<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey\Migration;

use Magento\Framework\App\DeploymentConfig\Reader as ConfigReader;
use Magento\Framework\App\DeploymentConfig\Writer as ConfigWriter;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection\ConnectionFactory;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\DB\Adapter\Pdo\Mysql as MysqlAdapter;
use Magento\Setup\Console\Command\AbstractSetupCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract class. Inherited by SplitSales and SplitQuote
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @deprecated split database solution is deprecated and will be removed
 */
abstract class AbstractCommand extends AbstractSetupCommand
{
    /**
     * DB host name
     */
    const HOST = 'host';

    /**
     * Checkout DB name
     */
    const DB_NAME = 'dbname';

    /**
     * Checkout DB user
     */
    const USER_NAME = 'username';

    /**
     * Checkout DB user password
     */
    const PASSWORD = 'password';

    /**
     * New connection name
     */
    const CONNECTION = 'connection';

    /**
     * Linked resource name
     */
    const RESOURCE = 'resource';

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * Table names to migrate
     *
     * @var string[]
     * @deprecated 100.0.2 @see $tableIterators
     */
    protected $tables;

    /**
     * @var \Iterator[]
     */
    private $tableIterators;

    /**
     * @var TableNameArrayIteratorFactory
     */
    private $tableNameIteratorFactory;

    /**
     * @param ConfigWriter $configWriter
     * @param ConfigReader $configReader
     * @param ConnectionFactory $connectionFactory
     * @param string[] $tables
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     * @param \Iterator[] $tableIterators
     * @param TableNameArrayIteratorFactory $tableNameIteratorFactory
     * @throws \LogicException When the command name is empty
     *
     * @api
     */
    public function __construct(
        ConfigWriter $configWriter,
        ConfigReader $configReader,
        ConnectionFactory $connectionFactory,
        $tables = [],
        $name = null,
        array $tableIterators = [],
        TableNameArrayIteratorFactory $tableNameIteratorFactory = null
    ) {
        $this->configWriter = $configWriter;
        $this->configReader = $configReader;
        $this->connectionFactory = $connectionFactory;
        $this->tables = $tables;
        $this->tableIterators = $tableIterators;
        $this->tableNameIteratorFactory = $tableNameIteratorFactory ?: ObjectManager::getInstance()->get(
            TableNameArrayIteratorFactory::class
        );
        parent::__construct($name);
    }

    /**
     * Get command name
     *
     * @return string
     */
    abstract protected function getCommandName();

    /**
     * Get command description
     *
     * @return string
     */
    abstract protected function getCommandDescription();

    /**
     * Get command definition
     *
     * @return array
     */
    abstract protected function getCommandDefinition();

    /**
     * Get table names to migrate
     *
     * @return string[]
     * @deprecated 100.1.3 @see $tableIterators
     */
    protected function getTables()
    {
        return $this->tables;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName($this->getCommandName())
            ->setDescription($this->getCommandDescription())
            ->setDefinition($this->getCommandDefinition());
        parent::configure();
    }

    /**
     * @inheritdoc
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->generateConfig($input);
        $this->createConnection($config['db']['connection']['default']);
        $this->createConnection($config['db']['connection'][$input->getOption(self::CONNECTION)]);
        //backwards compatible workaround for array
        if ($this->getTables()) {
            $this->tableIterators[] = $this->tableNameIteratorFactory->create(['tableNames' => $this->getTables()]);
        }
        // Get connections and disable foreign key checks
        $defaultConnection = $this->createConnection($config['db']['connection']['default']);
        $newConnection = $this->createConnection($config['db']['connection'][$input->getOption(self::CONNECTION)]);

        foreach ($this->tableIterators as $iterator) {
            foreach ($iterator as $tableName) {
                if ($defaultConnection->isTableExists($tableName)) {
                    $this->moveTable($defaultConnection, $newConnection, $tableName);
                }
            }
        }

        //04. Drop foreign keys between connections and enable foreign key checks
        $this->dropForeignKeys($newConnection, $defaultConnection);
        $this->dropForeignKeys($defaultConnection, $newConnection);
        $this->configWriter->saveConfig([ConfigFilePool::APP_ENV => $config], true);
        $output->writeln('Configuration has been updated! Run bin/magento setup:upgrade!');
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /**
     * Move table from first connection to second connection
     *
     * @param MysqlAdapter $firstConnection
     * @param MysqlAdapter $secondConnection
     * @param string $tableName
     * @return void
     */
    protected function moveTable(MysqlAdapter $firstConnection, MysqlAdapter $secondConnection, $tableName)
    {
        //Migrate schema to second connection
        $secondSchemaName = $secondConnection->getConfig()['dbname'];
        if ($secondConnection->isTableExists($tableName, $secondSchemaName) === false) {
            $secondConnection->query($firstConnection->getCreateTable($tableName));
        }

        //Migrate data to second connection
        $select = $firstConnection->select()->from($tableName);
        $data = $firstConnection->query($select)->fetchAll();
        if (count($data)) {
            $columns = array_keys($data[0]);
            $secondConnection->insertArray($tableName, $columns, $data);
        }

        //Drop table from first connection
        $firstConnection->dropTable($tableName);
    }

    /**
     * Drop foreign keys from firstConnection to secondConnection
     *
     * @param MysqlAdapter $firstConnection
     * @param MysqlAdapter $secondConnection
     * @return void
     */
    protected function dropForeignKeys(MysqlAdapter $firstConnection, MysqlAdapter $secondConnection)
    {
        foreach ($firstConnection->getTables() as $tableName) {
            foreach ($firstConnection->getForeignKeys($tableName) as $keyInfo) {
                if (in_array($keyInfo['REF_TABLE_NAME'], $secondConnection->getTables())) {
                    $firstConnection->dropForeignKey($keyInfo['TABLE_NAME'], $keyInfo['FK_NAME']);
                }
            }
        }
        $firstConnection->query('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Create DB connection
     *
     * @param array $config connection config
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createConnection($config)
    {
        $connection = $this->connectionFactory->create($config);
        $connection->query('SET FOREIGN_KEY_CHECKS=0;');
        return $connection;
    }

    /**
     * Generate environment configuration
     *
     * @param InputInterface $input
     * @return array
     * @throws \Exception
     */
    protected function generateConfig(InputInterface $input)
    {
        $config = $this->configReader->load(ConfigFilePool::APP_ENV);

        if (!isset($config['db']['connection'][$input->getOption(self::CONNECTION)])) {
            $config['db']['connection'][$input->getOption(self::CONNECTION)] = [
                'host' => $input->getOption(self::HOST),
                'dbname' => $input->getOption(self::DB_NAME),
                'username' => $input->getOption(self::USER_NAME),
                'password' => $input->getOption(self::PASSWORD),
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
            ];
        }

        if (!isset($config['resource'][$input->getOption(self::RESOURCE)])) {
            $config['resource'][$input->getOption(self::RESOURCE)] = [
                'connection' => $input->getOption(self::CONNECTION)
            ];
        }
        return $config;
    }
}
