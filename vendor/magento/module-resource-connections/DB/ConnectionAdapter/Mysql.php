<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ResourceConnections\DB\ConnectionAdapter;

use Magento\ResourceConnections\DB\Adapter\Pdo\MysqlProxy;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DB;
use Magento\Framework\DB\Adapter\Pdo\MysqlFactory;
use Magento\Framework\DB\SelectFactory;

class Mysql extends \Magento\Framework\Model\ResourceModel\Type\Db\Pdo\Mysql
{
    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * Constructor
     *
     * @param array $config
     * @param HttpRequest $request
     * @param MysqlFactory|null $mysqlFactory
     */
    public function __construct(
        array $config,
        HttpRequest $request,
        MysqlFactory $mysqlFactory = null
    ) {
        parent::__construct($config, $mysqlFactory);
        $this->request = $request;
    }

    /**
     * @inheritdoc
     */
    protected function getDbConnectionClassName()
    {
        if (isset($this->connectionConfig['slave']) &&
            ($this->request->isSafeMethod() || ($this->connectionConfig['slave']['synchronous_replication'] ?? false))
        ) {
            return MysqlProxy::class;
        }
        unset($this->connectionConfig['slave']);
        return \Magento\Framework\DB\Adapter\Pdo\Mysql::class;
    }

    /**
     * @inheritdoc
     */
    public function getConnection(DB\LoggerInterface $logger = null, SelectFactory $selectFactory = null)
    {
        $connection = $this->getDbConnectionInstance($logger, $selectFactory);
        if ($connection instanceof \Magento\Framework\DB\Adapter\Pdo\Mysql) {
            $profiler = $connection->getProfiler();
            if ($profiler instanceof DB\Profiler) {
                $profiler->setType($this->connectionConfig['type']);
                $profiler->setHost($this->connectionConfig['host']);
            }
        }
        return $connection;
    }
}
