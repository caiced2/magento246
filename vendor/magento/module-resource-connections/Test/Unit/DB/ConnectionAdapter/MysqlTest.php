<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ResourceConnections\Test\Unit\DB\ConnectionAdapter;

use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Adapter\Pdo\MysqlFactory;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\DB\SelectFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ResourceConnections\DB\Adapter\Pdo\MysqlProxy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MysqlTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\App\Request\Http|MockObject
     */
    private $requestMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var SelectFactory|MockObject
     */
    private $selectFactoryMock;

    /**
     * @var MysqlFactory|MockObject
     */
    private $mysqlFactoryMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->requestMock = $this->createPartialMock(RequestHttp::class, ['isSafeMethod']);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->selectFactoryMock = $this->createMock(SelectFactory::class);
        $this->mysqlFactoryMock = $this->createMock(MysqlFactory::class);
    }

    /**
     * Test that real adapter is returned for non-safe method
     */
    public function testInstantiationForNonSafeMethodWithoutSlave()
    {
        $config = [
            'host' => 'testHost',
            'active' => true,
            'initStatements' => 'SET NAMES utf8',
            'type' => 'pdo_mysql'
        ];
        $this->requestMock->expects($this->never())
            ->method('isSafeMethod')
            ->willReturn(false);
        $this->assertCreateAdapter(
            Mysql::class,
            $config,
            $config
        );
    }

    /**
     * Test that real adapter is returned for non-safe method even if slave is set
     */
    public function testInstantiationForSafeMethodWithSlave()
    {
        $config = [
            'host' => 'testHost',
            'active' => true,
            'initStatements' => 'SET NAMES utf8',
            'type' => 'pdo_mysql',
            'slave' => [
                'host' => 'slaveHost'
            ]
        ];
        $expectedBuildConfig = $config;
        unset($expectedBuildConfig['slave']);
        $this->requestMock->expects($this->once())
            ->method('isSafeMethod')
            ->willReturn(false);
        $this->assertCreateAdapter(
            Mysql::class,
            $config,
            $expectedBuildConfig
        );
    }

    /**
     * Test that real adapter is returned for safe method if slave is not set
     */
    public function testInstantiationForSafeRequestWithoutSlave()
    {
        $config = [
            'host' => 'testHost',
            'active' => true,
            'initStatements' => 'SET NAMES utf8',
            'type' => 'pdo_mysql',
        ];
        $this->requestMock->expects($this->never())
            ->method('isSafeMethod');
        $this->assertCreateAdapter(
            Mysql::class,
            $config,
            $config
        );
    }

    /**
     * Test that adapter proxy is returned for safe method if slave config is set
     */
    public function testInstantiationForSafeRequestWithSlave()
    {
        $config = [
            'host' => 'testHost',
            'active' => true,
            'initStatements' => 'SET NAMES utf8',
            'type' => 'pdo_mysql',
            'slave' => [
                'host' => 'slaveHost'
            ]
        ];
        $this->requestMock->expects($this->once())
            ->method('isSafeMethod')
            ->willReturn(true);
        $this->assertCreateAdapter(
            MysqlProxy::class,
            $config,
            $config
        );
    }

    /**
     * Create Mysql adapter, assert that factory used with correct arguments
     *
     * @param string $expectedClassName
     * @param array $config
     * @param array $expectedConfig
     * @return void
     */
    private function assertCreateAdapter(
        $expectedClassName,
        array $config,
        array $expectedConfig
    ) {
        $this->mysqlFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $expectedClassName,
                $expectedConfig,
                $this->loggerMock
            );
        $mysqlAdapter = $this->objectManager->getObject(
            \Magento\ResourceConnections\DB\ConnectionAdapter\Mysql::class,
            [
                'config' => $config,
                'request' => $this->requestMock,
                'mysqlFactory' => $this->mysqlFactoryMock
            ]
        );
        $mysqlAdapter->getConnection($this->loggerMock, $this->selectFactoryMock);
    }
}
